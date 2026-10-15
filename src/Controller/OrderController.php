<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Auth;
use App\Core\Http;
use App\Core\Session;
use App\Core\View;
use App\Repository\HoraireRepository;
use App\Repository\MenuRepository;
use App\Repository\OrderRepository;
use App\Repository\ProfileRepository;
use App\Service\DistanceException;
use App\Service\DistanceService;
use App\Service\OrderException;
use App\Service\OrderMailer;
use App\Service\OrderService;
use App\Service\Pricing;
use App\Validation\OrderValidator;
use Throwable;

final class OrderController
{
    private const CHAMPS = ['menu_id', 'personnes', 'date_prestation', 'heure_livraison',
                            'adresse_prestation', 'code_postal_prestation', 'ville_prestation'];

    /** Étape 1 : formulaire (GET /commande?menu=ID, menu pré-sélectionné depuis la vue détaillée). */
    public function create(): void
    {
        $user = Auth::requireRole(['utilisateur']);
        $profil = (new ProfileRepository())->find((int) $user['id']);

        $parametre = $_GET['menu'] ?? null;
        $menuId = $this->id($parametre);
        if ($parametre !== null && $menuId === null) {
            Http::notFound();
        }

        $old = [
            'menu_id' => $menuId !== null ? (string) $menuId : '',
            'personnes' => '',
            'date_prestation' => '',
            'heure_livraison' => '',
            'adresse_prestation' => (string) ($profil['adresse_postale'] ?? ''),
            'code_postal_prestation' => (string) ($profil['code_postal'] ?? ''),
            'ville_prestation' => (string) ($profil['ville'] ?? ''),
        ];

        if ($menuId !== null) {
            $menu = (new MenuRepository())->find($menuId);
            if ($menu === null) {
                Http::notFound();
            }
            $old['personnes'] = (string) $menu['nombre_personne_minimum']; // pré-rempli au minimum
        }

        $this->formulaire($profil, $old, []);
    }

    /** Étape 2 : récapitulatif avec le prix détaillé (POST /commande/recapitulatif). */
    public function recap(): void
    {
        $user = $this->exigerUtilisateur();
        $profil = (new ProfileRepository())->find((int) $user['id']);
        $lecture = $this->lire();

        if ($lecture['errors'] !== []) {
            $this->formulaire($profil, $lecture['old'], $lecture['errors'], 422);
            return;
        }

        try {
            $distance = $this->distance($lecture['in']);
        } catch (DistanceException $e) {
            $this->formulaire($profil, $lecture['old'], ['adresse_prestation' => $e->getMessage()], 422);
            return;
        }

        $this->recapitulatif($lecture['menu'], $lecture['in'], $distance, [], null);
    }

    /** Retour du récapitulatif vers le formulaire, sans perdre la saisie. */
    public function modifier(): void
    {
        $user = $this->exigerUtilisateur();
        $profil = (new ProfileRepository())->find((int) $user['id']);
        $lecture = $this->lire();
        $this->formulaire($profil, $lecture['old'], []);
    }

    /** Étape 3 : confirmation et enregistrement (POST /commande). */
    public function store(): void
    {
        $user = $this->exigerUtilisateur();
        $profil = (new ProfileRepository())->find((int) $user['id']);
        $lecture = $this->lire();

        if ($lecture['errors'] !== []) {
            $this->formulaire($profil, $lecture['old'], $lecture['errors'], 422);
            return;
        }

        $menu = $lecture['menu'];
        $in = $lecture['in'];

        try {
            $distance = $this->distance($in);
        } catch (DistanceException $e) {
            $this->formulaire($profil, $lecture['old'], ['adresse_prestation' => $e->getMessage()], 422);
            return;
        }

        $devis = Pricing::devis(Pricing::toCents($menu['prix_minimum']), (int) $menu['nombre_personne_minimum'],
            $in['personnes'], $distance['bordeaux'], $distance['km10']);

        // Le prix confirmé doit être celui que le client a vu : sinon on le lui remontre
        $attendu = is_string($_POST['total_centimes'] ?? null) && preg_match('/^\d{1,9}$/', $_POST['total_centimes'])
            ? (int) $_POST['total_centimes'] : null;
        if ($attendu === null || $attendu !== $devis['total']) {
            $this->recapitulatif($menu, $in, $distance, [], 'Le prix de votre commande a été mis à jour. Vérifiez le détail ci-dessous avant de confirmer.');
            return;
        }

        if (($_POST['conditions'] ?? '') !== '1') {
            $this->recapitulatif($menu, $in, $distance, [
                'conditions' => 'Vous devez accepter les conditions de ce menu et les conditions générales de vente pour confirmer.',
            ], null, 422);
            return;
        }

        try {
            $commande = (new OrderService())->place((int) $user['id'], $in, $distance, $attendu);
        } catch (OrderException $e) {
            if ($e->raison === 'prix') {
                $this->recapitulatif($menu, $in, $distance, [], $e->getMessage());
                return;
            }
            $champ = match ($e->raison) {
                'minimum' => 'personnes',
                'delai' => 'date_prestation',
                default => 'menu_id',
            };
            $this->formulaire($profil, $lecture['old'], [$champ => $e->getMessage()], 409);
            return;
        }

        try {
            (new OrderMailer())->sendConfirmation((string) $profil['email'], $profil['prenom'] ?? null, $in + [
                'numero' => $commande['numero'],
                'menu_titre' => $commande['menu_titre'],
                'devis' => $commande['devis'],
                'distance' => $distance,
            ]);
        } catch (Throwable $e) {
            error_log('Mail de confirmation de commande non envoyé : ' . $e->getMessage());
        }

        Session::forget('devis_distance');
        Session::flash('success', 'Votre commande a bien été enregistrée.');
        Http::redirect('/commande/confirmation/' . $commande['numero']);
    }

    public function confirmation(string $numero): void
    {
        $user = Auth::requireRole(['utilisateur']);

        if (!preg_match('/^VG-\d{4}-\d{6}$/', $numero)) {
            Http::notFound();
        }
        $commande = (new OrderRepository())->findByNumero($numero, (int) $user['id']);
        if ($commande === null) { // inexistante ou appartenant à un autre client : même réponse
            Http::notFound();
        }

        View::render('orders/confirmation', ['title' => 'Commande ' . $numero, 'commande' => $commande]);
    }

    /** Estimation du prix du menu pendant la saisie (JavaScript du formulaire). Données publiques. */
    public function price(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $flags = JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR;

        $menuId = $this->id($_GET['menu'] ?? null);
        $menu = $menuId !== null ? (new MenuRepository())->find($menuId) : null;
        $texte = $_GET['personnes'] ?? null;

        if ($menu === null || !is_string($texte) || !preg_match('/^[1-9]\d{0,3}$/', $texte)) {
            http_response_code(422);
            echo json_encode(['errors' => ['personnes' => 'Saisissez un nombre de personnes valide.']], $flags);
            return;
        }

        $personnes = (int) $texte;
        $minimum = (int) $menu['nombre_personne_minimum'];
        if ($personnes < $minimum) {
            http_response_code(422);
            echo json_encode(['errors' => ['personnes' => "Ce menu se commande pour $minimum personnes minimum."]], $flags);
            return;
        }

        $brut = Pricing::prixMenuBrut(Pricing::toCents($menu['prix_minimum']), $minimum, $personnes);
        $remise = Pricing::remise($brut, $minimum, $personnes);

        echo json_encode([
            'menu_id' => (int) $menu['id'],
            'minimum' => $minimum,
            'personnes' => $personnes,
            'brut_centimes' => $brut,
            'remise_centimes' => $remise,
            'menu_centimes' => $brut - $remise,
            'seuil_remise' => Pricing::seuilRemise($minimum),
            'remise_active' => $remise > 0,
        ], $flags);
    }

    // ---------- Outils ----------

    /** Les POST ne gardent pas de destination : une session expirée renvoie vers le formulaire. */
    private function exigerUtilisateur(): array
    {
        if (Auth::user() === null) {
            Session::flash('info', 'Votre session a expiré. Veuillez vous reconnecter pour passer commande.');
            Http::redirect('/connexion?redirect=' . rawurlencode('/commande'));
        }

        return Auth::requireRole(['utilisateur']);
    }

    private function id(mixed $valeur): ?int
    {
        return is_string($valeur) && preg_match('/^[1-9]\d{0,9}$/', $valeur) ? (int) $valeur : null;
    }

    /** @return array{menu:?array,in:array,errors:array,old:array} */
    private function lire(): array
    {
        $old = [];
        foreach (self::CHAMPS as $champ) {
            $old[$champ] = is_string($_POST[$champ] ?? null) ? mb_substr(trim($_POST[$champ]), 0, 255) : '';
        }

        $menuId = $this->id($old['menu_id']);
        $menu = $menuId !== null ? (new MenuRepository())->find($menuId) : null;
        if ($menu === null) {
            return ['menu' => null, 'in' => [], 'errors' => ['menu_id' => 'Choisissez un menu.'], 'old' => $old];
        }

        $errors = [];
        if ((int) $menu['stock'] <= 0) {
            $errors['menu_id'] = "Ce menu n'est plus disponible pour le moment.";
        }

        $horaires = new HoraireRepository();
        $v = OrderValidator::validate($_POST, $menu, static fn (int $jour): ?array => $horaires->forDay($jour));

        return ['menu' => $menu, 'in' => $v['data'], 'errors' => $errors + $v['errors'], 'old' => $old];
    }

    /** Distance mémorisée 30 minutes : le prix confirmé est celui du récapitulatif, sans second appel externe. */
    private function distance(array $in): array
    {
        $cle = hash('sha256', (string) json_encode(
            [$in['adresse_prestation'], $in['code_postal_prestation'], $in['ville_prestation']],
            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        ));
        $cache = Session::get('devis_distance');
        if (is_array($cache) && ($cache['cle'] ?? null) === $cle && (int) ($cache['expire'] ?? 0) > time() && isset($cache['distance'])) {
            return $cache['distance'];
        }

        $distance = (new DistanceService())->compute($in['adresse_prestation'], $in['code_postal_prestation'], $in['ville_prestation']);
        Session::set('devis_distance', ['cle' => $cle, 'expire' => time() + 1800, 'distance' => $distance]);

        return $distance;
    }

    private function formulaire(?array $profil, array $old, array $errors, int $statut = 200): void
    {
        http_response_code($statut);
        View::render('orders/new', [
            'title' => 'Commander',
            'profil' => $profil,
            'menus' => (new MenuRepository())->search(),
            'old' => $old,
            'errors' => $errors,
        ]);
    }

    private function recapitulatif(array $menu, array $in, array $distance, array $errors, ?string $notice, int $statut = 200): void
    {
        $devis = Pricing::devis(Pricing::toCents($menu['prix_minimum']), (int) $menu['nombre_personne_minimum'],
            $in['personnes'], $distance['bordeaux'], $distance['km10']);

        http_response_code($statut);
        View::render('orders/recap', [
            'title' => 'Récapitulatif de votre commande',
            'menu' => $menu,
            'in' => $in,
            'distance' => $distance,
            'devis' => $devis,
            'errors' => $errors,
            'notice' => $notice,
        ]);
    }
}
