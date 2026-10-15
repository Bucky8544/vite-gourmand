<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Auth;
use App\Core\Http;
use App\Core\Money;
use App\Core\Session;
use App\Core\View;
use App\Repository\HoraireRepository;
use App\Repository\OrderRepository;
use App\Repository\ProfileRepository;
use App\Service\DistanceException;
use App\Service\DistanceService;
use App\Service\OrderEditService;
use App\Service\OrderException;
use App\Service\OrderStatus;
use App\Service\Pricing;
use App\Service\ReviewService;
use App\Validation\OrderValidator;
use App\Validation\ProfileValidator;
use App\Validation\ReviewValidator;

final class UserSpaceController
{
    private const CHAMPS_COMMANDE = ['personnes', 'date_prestation', 'heure_livraison',
                                     'adresse_prestation', 'code_postal_prestation', 'ville_prestation'];

    // ---------- Tableau de bord ----------
    public function index(): void
    {
        $user = Auth::requireRole(['utilisateur']);

        View::render('espace/index', [
            'title' => 'Mon espace',
            'current' => '/mon-espace',
            'profil' => (new ProfileRepository())->find((int) $user['id']),
            'commandes' => (new OrderRepository())->listForUser((int) $user['id']),
        ]);
    }

    // ---------- Détail d'une commande ----------
    public function show(string $numero): void
    {
        $user = Auth::requireRole(['utilisateur']);
        $this->afficherCommande($this->commande($numero, (int) $user['id']), [], []);
    }

    // ---------- Modification ----------
    public function modifierForm(string $numero): void
    {
        $user = Auth::requireRole(['utilisateur']);
        $c = $this->commande($numero, (int) $user['id']);
        $this->exigerModifiable($c);

        $this->formulaireModification($c, [
            'personnes' => (string) $c['nombre_personne'],
            'date_prestation' => (string) $c['date_prestation'],
            'heure_livraison' => substr((string) $c['heure_livraison'], 0, 5),
            'adresse_prestation' => (string) $c['adresse_prestation'],
            'code_postal_prestation' => (string) $c['code_postal_prestation'],
            'ville_prestation' => (string) $c['ville_prestation'],
        ], []);
    }

    public function modifierRecap(string $numero): void
    {
        $user = $this->utilisateur();
        $c = $this->commande($numero, (int) $user['id']);
        $this->exigerModifiable($c);
        $lecture = $this->lireModification($c);

        if ($lecture['errors'] !== []) {
            $this->formulaireModification($c, $lecture['old'], $lecture['errors'], 422);
            return;
        }
        try {
            $distance = $this->distance($lecture['in']);
        } catch (DistanceException $e) {
            $this->formulaireModification($c, $lecture['old'], ['adresse_prestation' => $e->getMessage()], 422);
            return;
        }

        $this->recapModification($c, $lecture['in'], $distance, null);
    }

    /** Retour du récapitulatif vers le formulaire, sans perdre la saisie. */
    public function modifierRetour(string $numero): void
    {
        $user = $this->utilisateur();
        $c = $this->commande($numero, (int) $user['id']);
        $this->exigerModifiable($c);
        $this->formulaireModification($c, $this->lireModification($c)['old'], []);
    }

    public function modifierConfirmer(string $numero): void
    {
        $user = $this->utilisateur();
        $c = $this->commande($numero, (int) $user['id']);
        $this->exigerModifiable($c);
        $lecture = $this->lireModification($c);

        if ($lecture['errors'] !== []) {
            $this->formulaireModification($c, $lecture['old'], $lecture['errors'], 422);
            return;
        }
        try {
            $distance = $this->distance($lecture['in']);
        } catch (DistanceException $e) {
            $this->formulaireModification($c, $lecture['old'], ['adresse_prestation' => $e->getMessage()], 422);
            return;
        }

        $devis = Pricing::devis(Pricing::toCents($c['prix_minimum']), (int) $c['nombre_personne_minimum'],
            $lecture['in']['personnes'], $distance['bordeaux'], $distance['km10']);

        $attendu = is_string($_POST['total_centimes'] ?? null) && preg_match('/^\d{1,9}$/', $_POST['total_centimes'])
            ? (int) $_POST['total_centimes'] : null;
        if ($attendu === null || $attendu !== $devis['total']) {
            $this->recapModification($c, $lecture['in'], $distance, 'Le prix a été mis à jour. Vérifiez le détail ci-dessous avant de confirmer.');
            return;
        }

        try {
            (new OrderEditService())->modify((int) $user['id'], $numero, $lecture['in'], $distance, $attendu);
        } catch (OrderException $e) {
            if ($e->raison === 'prix') {
                $this->recapModification($c, $lecture['in'], $distance, $e->getMessage());
                return;
            }
            if ($e->raison === 'statut' || $e->raison === 'introuvable') {
                Session::flash('danger', $e->getMessage());
                Http::redirect('/mon-espace/commandes/' . $numero);
            }
            $champ = $e->raison === 'minimum' ? 'personnes' : 'date_prestation';
            $this->formulaireModification($c, $lecture['old'], [$champ => $e->getMessage()], 409);
            return;
        }

        Session::forget('devis_distance');
        Session::flash('success', 'Votre commande a été modifiée. Nouveau total : ' . Money::format($devis['total']) . '.');
        Http::redirect('/mon-espace/commandes/' . $numero);
    }

    // ---------- Annulation ----------
    public function annulerForm(string $numero): void
    {
        $user = Auth::requireRole(['utilisateur']);
        $c = $this->commande($numero, (int) $user['id']);
        $this->exigerModifiable($c);

        View::render('espace/annuler', ['title' => 'Annuler la commande ' . $numero, 'current' => '/mon-espace', 'commande' => $c]);
    }

    public function annuler(string $numero): void
    {
        $user = $this->utilisateur();
        $this->commande($numero, (int) $user['id']);

        try {
            (new OrderEditService())->cancel((int) $user['id'], $numero);
        } catch (OrderException $e) {
            Session::flash('danger', $e->getMessage());
            Http::redirect('/mon-espace/commandes/' . $numero);
        }

        Session::flash('success', 'Votre commande ' . $numero . ' a été annulée.');
        Http::redirect('/mon-espace/commandes/' . $numero);
    }

    // ---------- Avis ----------
    public function avis(string $numero): void
    {
        $user = $this->utilisateur();
        $c = $this->commande($numero, (int) $user['id']);

        $v = ReviewValidator::validate($_POST);
        if ($v['errors'] !== []) {
            $this->afficherCommande($c, $v['errors'], $v['old'], 422);
            return;
        }

        try {
            (new ReviewService())->submit((int) $user['id'], $numero, $v['data']['note'], $v['data']['commentaire']);
        } catch (OrderException $e) {
            Session::flash('danger', $e->getMessage());
            Http::redirect('/mon-espace/commandes/' . $numero);
        }

        Session::flash('success', 'Merci pour votre avis ! Il sera publié après validation par notre équipe.');
        Http::redirect('/mon-espace/commandes/' . $numero);
    }

    // ---------- Informations personnelles ----------
    public function profil(): void
    {
        $user = Auth::requireRole(['utilisateur']);
        $profil = (new ProfileRepository())->find((int) $user['id']);

        View::render('espace/profil', [
            'title' => 'Mes informations',
            'current' => '/mon-espace',
            'profil' => $profil,
            'old' => $profil ?? [],
            'errors' => [],
        ]);
    }

    public function profilEnregistrer(): void
    {
        $user = $this->utilisateur();
        $repo = new ProfileRepository();
        $v = ProfileValidator::validate($_POST);

        if ($v['errors'] !== []) {
            http_response_code(422);
            View::render('espace/profil', [
                'title' => 'Mes informations',
                'current' => '/mon-espace',
                'profil' => $repo->find((int) $user['id']),
                'old' => $v['data'],
                'errors' => $v['errors'],
            ]);
            return;
        }

        $repo->update((int) $user['id'], $v['data']);
        Session::flash('success', 'Vos informations ont été enregistrées.');
        Http::redirect('/mon-espace');
    }

    // ---------- Outils ----------

    /** Les POST ne gardent pas de destination : une session expirée renvoie vers l'espace. */
    private function utilisateur(): array
    {
        if (Auth::user() === null) {
            Session::flash('info', 'Votre session a expiré. Veuillez vous reconnecter.');
            Http::redirect('/connexion?redirect=' . rawurlencode('/mon-espace'));
        }

        return Auth::requireRole(['utilisateur']);
    }

    private function commande(string $numero, int $userId): array
    {
        if (!preg_match('/^VG-\d{4}-\d{6}$/', $numero)) {
            Http::notFound();
        }
        $commande = (new OrderRepository())->findByNumero($numero, $userId);
        if ($commande === null) { // inexistante ou appartenant à un autre client : même réponse
            Http::notFound();
        }

        return $commande;
    }

    private function exigerModifiable(array $c): void
    {
        if (!OrderStatus::modifiable((string) $c['statut'])) {
            Session::flash('danger', OrderEditService::MESSAGE_PRISE_EN_CHARGE);
            Http::redirect('/mon-espace/commandes/' . $c['numero_commande']);
        }
    }

    private function afficherCommande(array $c, array $errors, array $old, int $statut = 200): void
    {
        $repo = new OrderRepository();
        $historique = $repo->history((int) $c['id']);
        $avis = $repo->avis((int) $c['id']);

        http_response_code($statut);
        View::render('espace/commande', [
            'title' => 'Commande ' . $c['numero_commande'],
            'current' => '/mon-espace',
            'commande' => $c,
            'historique' => $historique,
            'suiviVisible' => OrderStatus::suiviVisible($historique),
            'modifiable' => OrderStatus::modifiable((string) $c['statut']),
            'avis' => $avis,
            'peutDonnerAvis' => $c['statut'] === 'terminee' && $avis === null,
            'errors' => $errors,
            'old' => $old,
        ]);
    }

    private function formulaireModification(array $c, array $old, array $errors, int $statut = 200): void
    {
        http_response_code($statut);
        View::render('espace/modifier', [
            'title' => 'Modifier la commande ' . $c['numero_commande'],
            'current' => '/mon-espace',
            'commande' => $c,
            'old' => $old,
            'errors' => $errors,
        ]);
    }

    private function recapModification(array $c, array $in, array $distance, ?string $notice, int $statut = 200): void
    {
        $devis = Pricing::devis(Pricing::toCents($c['prix_minimum']), (int) $c['nombre_personne_minimum'],
            $in['personnes'], $distance['bordeaux'], $distance['km10']);

        http_response_code($statut);
        View::render('espace/modifier_recap', [
            'title' => 'Récapitulatif de la modification',
            'current' => '/mon-espace',
            'commande' => $c,
            'in' => $in,
            'distance' => $distance,
            'devis' => $devis,
            'ancienTotal' => Pricing::toCents($c['prix_total']),
            'notice' => $notice,
        ]);
    }

    /** @return array{in:array,errors:array,old:array} */
    private function lireModification(array $c): array
    {
        $old = [];
        foreach (self::CHAMPS_COMMANDE as $champ) {
            $old[$champ] = is_string($_POST[$champ] ?? null) ? mb_substr(trim($_POST[$champ]), 0, 255) : '';
        }

        // Le délai du menu ne s'applique que si la date change
        $memeDate = $old['date_prestation'] === (string) $c['date_prestation'];
        $menu = [
            'id' => (int) $c['menu_id'],
            'nombre_personne_minimum' => (int) $c['nombre_personne_minimum'],
            'delai_commande_jours' => $memeDate ? 0 : (int) $c['delai_commande_jours'],
        ];

        $horaires = new HoraireRepository();
        $v = OrderValidator::validate($_POST, $menu, static fn (int $jour): ?array => $horaires->forDay($jour));

        return ['in' => $v['data'], 'errors' => $v['errors'], 'old' => $old];
    }

    /** Distance mémorisée 30 minutes : le prix confirmé est celui du récapitulatif. */
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
}
