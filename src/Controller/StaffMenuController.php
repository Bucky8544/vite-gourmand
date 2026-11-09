<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Auth;
use App\Core\Http;
use App\Core\Session;
use App\Core\View;
use App\Repository\MenuAdminRepository;
use App\Repository\PlatAdminRepository;
use App\Service\CatalogException;
use App\Service\ImageStorage;
use App\Service\MenuAdminService;
use App\Validation\MenuValidator;

/** Gestion des menus par les employés et l'administrateur. */
final class StaffMenuController
{
    private const ROLES = ['employe', 'administrateur'];
    private const SLOTS = 3; // nouvelles images par envoi

    public function index(): void
    {
        Auth::requireRole(self::ROLES);

        View::render('employe/menus', [
            'title' => 'Menus',
            'current' => '/employe',
            'menus' => (new MenuAdminRepository())->listAll(),
        ]);
    }

    public function nouveau(): void
    {
        Auth::requireRole(self::ROLES);
        $this->formulaire(null, [
            'titre' => '', 'description' => '', 'conditions' => '', 'theme_id' => '', 'regime_id' => '',
            'nombre_personne_minimum' => '4', 'prix_minimum' => '', 'stock' => '10', 'delai_commande_jours' => '7',
            'actif' => '1', 'plats' => [], 'images' => [],
        ], []);
    }

    public function creer(): void
    {
        $this->traiter(null);
    }

    public function modifierForm(string $id): void
    {
        Auth::requireRole(self::ROLES);
        $menuId = $this->id($id);
        $repo = new MenuAdminRepository();
        $menu = $repo->find($menuId);
        if ($menu === null) {
            Http::notFound();
        }

        $this->formulaire($menuId, [
            'titre' => $menu['titre'], 'description' => $menu['description'], 'conditions' => $menu['conditions'],
            'theme_id' => (string) $menu['theme_id'], 'regime_id' => (string) $menu['regime_id'],
            'nombre_personne_minimum' => (string) $menu['nombre_personne_minimum'],
            'prix_minimum' => number_format((float) $menu['prix_minimum'], 2, ',', ''),
            'stock' => (string) $menu['stock'], 'delai_commande_jours' => (string) $menu['delai_commande_jours'],
            'actif' => (int) $menu['actif'] === 1 ? '1' : '', 'plats' => $repo->platIds($menuId), 'images' => [],
        ], []);
    }

    public function modifier(string $id): void
    {
        $this->traiter($this->id($id));
    }

    public function basculer(string $id): void
    {
        $this->personnel();
        $menuId = $this->id($id);
        $actif = ($_POST['actif'] ?? '') === '1';

        try {
            (new MenuAdminService())->setActive($menuId, $actif);
        } catch (CatalogException $e) {
            Http::notFound();
        }

        Session::flash('success', $actif ? 'Le menu est de nouveau visible sur le site.' : 'Le menu est désactivé : il n\'est plus visible sur le site.');
        Http::redirect('/employe/menus');
    }

    public function supprimer(string $id): void
    {
        $this->personnel();
        $menuId = $this->id($id);

        if (($_POST['confirmer'] ?? '') !== '1') {
            Session::flash('danger', 'Cochez la case de confirmation pour supprimer ce menu.');
            Http::redirect('/employe/menus/' . $menuId);
        }

        try {
            (new MenuAdminService())->delete($menuId);
        } catch (CatalogException $e) {
            if ($e->raison === 'introuvable') {
                Http::notFound();
            }
            Session::flash('danger', $e->getMessage());
            Http::redirect('/employe/menus/' . $menuId);
        }

        Session::flash('success', 'Le menu a été supprimé.');
        Http::redirect('/employe/menus');
    }

    // ---------- Outils ----------

    private function traiter(?int $id): void
    {
        $this->personnel();
        $repo = new MenuAdminRepository();
        if ($id !== null && $repo->find($id) === null) {
            Http::notFound();
        }

        $imagesBase = $id !== null ? $repo->images($id) : [];
        $v = MenuValidator::validate(
            $_POST,
            array_column($repo->themes(), 'libelle', 'id'),
            array_column($repo->regimes(), 'libelle', 'id'),
            array_map('intval', array_column((new PlatAdminRepository())->listAll(), 'id')),
            array_map('intval', array_column($imagesBase, 'id'))
        );
        $errors = $v['errors'];

        // Nouvelles images : chacune est vérifiée sur son contenu avant tout enregistrement
        $stockage = new ImageStorage();
        $nouvelles = [];
        for ($i = 0; $i < self::SLOTS; $i++) {
            $fichier = $this->fichier($i);
            $alt = is_array($_POST['nouvelle_alt'] ?? null) && is_string($_POST['nouvelle_alt'][$i] ?? null) ? trim($_POST['nouvelle_alt'][$i]) : '';

            $res = $stockage->inspecter($fichier);
            if (!empty($res['vide'])) {
                continue;
            }
            if (!$res['ok']) {
                $errors["nouvelle_image_$i"] = $res['erreur'];
                continue;
            }
            if (mb_strlen($alt) < 3 || mb_strlen($alt) > 255) {
                $errors["nouvelle_alt_$i"] = "Décrivez la nouvelle image (3 à 255 caractères) : ce texte est lu par les lecteurs d'écran.";
                continue;
            }
            $nouvelles[] = ['tmp' => (string) $fichier['tmp_name'], 'ext' => (string) $res['ext'], 'alt' => $alt];
        }

        $restantes = count(array_filter($v['data']['images'], static fn (array $img): bool => !$img['supprimer'])) + count($nouvelles);
        if ($restantes > MenuValidator::MAX_IMAGES) {
            $errors['images'] = 'Un menu peut avoir ' . MenuValidator::MAX_IMAGES . ' images au maximum.';
        }

        if ($errors !== []) {
            $this->formulaire($id, $v['old'], $errors, 422);
            return;
        }

        try {
            $menuId = (new MenuAdminService($stockage))->save($id, $v['data'], $v['data']['images'], $nouvelles);
        } catch (CatalogException $e) {
            Http::notFound();
        }

        Session::flash('success', $id === null ? 'Le menu a été créé.' : 'Le menu a été enregistré.');
        Http::redirect('/employe/menus/' . $menuId);
    }

    private function formulaire(?int $id, array $old, array $errors, int $statut = 200): void
    {
        $repo = new MenuAdminRepository();
        $parType = ['entree' => [], 'plat' => [], 'dessert' => []];
        foreach ((new PlatAdminRepository())->listAll() as $plat) {
            $parType[$plat['type']][] = $plat;
        }

        http_response_code($statut);
        View::render('employe/menu_form', [
            'title' => $id === null ? 'Nouveau menu' : 'Modifier le menu',
            'current' => '/employe',
            'id' => $id,
            'old' => $old,
            'errors' => $errors,
            'themes' => $repo->themes(),
            'regimes' => $repo->regimes(),
            'platsParType' => $parType,
            'images' => $id !== null ? $repo->images($id) : [],
            'nbCommandes' => $id !== null ? $repo->countOrders($id) : 0,
            'actifEnBase' => $id !== null ? (int) ($repo->find($id)['actif'] ?? 0) === 1 : false,
        ]);
    }

    /** Un fichier de $_FILES['nouvelle_image'] ; tolère une structure forgée. */
    private function fichier(int $i): array
    {
        $f = $_FILES['nouvelle_image'] ?? null;
        if (!is_array($f) || !is_array($f['name'] ?? null)) {
            return ['error' => UPLOAD_ERR_NO_FILE];
        }

        return [
            'name' => $f['name'][$i] ?? '',
            'tmp_name' => $f['tmp_name'][$i] ?? '',
            'error' => $f['error'][$i] ?? UPLOAD_ERR_NO_FILE,
            'size' => $f['size'][$i] ?? 0,
        ];
    }

    private function id(string $valeur): int
    {
        if (!preg_match('/^[1-9]\d{0,9}$/', $valeur)) {
            Http::notFound();
        }

        return (int) $valeur;
    }

    /** Les POST ne gardent pas de destination : une session expirée renvoie vers l'espace. */
    private function personnel(): array
    {
        if (Auth::user() === null) {
            Session::flash('info', 'Votre session a expiré. Veuillez vous reconnecter.');
            Http::redirect('/connexion?redirect=' . rawurlencode('/employe/menus'));
        }

        return Auth::requireRole(self::ROLES);
    }
}
