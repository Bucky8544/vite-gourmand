<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Auth;
use App\Core\Http;
use App\Core\Session;
use App\Core\View;
use App\Repository\HoraireAdminRepository;
use App\Repository\PlatAdminRepository;
use App\Repository\ReferentielRepository;
use App\Service\CatalogException;
use App\Service\PlatAdminService;
use App\Service\ReferentielService;
use App\Validation\HoraireValidator;
use App\Validation\PlatValidator;
use App\Validation\ReferentielValidator;

/** Plats, thèmes et régimes, horaires : le reste du catalogue géré par l'équipe. */
final class StaffCatalogController
{
    private const ROLES = ['employe', 'administrateur'];

    // ---------- Plats ----------
    public function plats(): void
    {
        Auth::requireRole(self::ROLES);
        View::render('employe/plats', ['title' => 'Plats', 'current' => '/employe', 'plats' => (new PlatAdminRepository())->listAll()]);
    }

    public function platNouveau(): void
    {
        Auth::requireRole(self::ROLES);
        $this->formulairePlat(null, ['titre' => '', 'type' => '', 'allergenes' => []], []);
    }

    public function platCreer(): void
    {
        $this->enregistrerPlat(null);
    }

    public function platModifierForm(string $id): void
    {
        Auth::requireRole(self::ROLES);
        $platId = $this->id($id);
        $plat = (new PlatAdminRepository())->find($platId);
        if ($plat === null) {
            Http::notFound();
        }
        $this->formulairePlat($platId, ['titre' => $plat['titre'], 'type' => $plat['type'], 'allergenes' => $plat['allergene_ids']], [], $plat);
    }

    public function platModifier(string $id): void
    {
        $this->enregistrerPlat($this->id($id));
    }

    public function platSupprimer(string $id): void
    {
        $this->personnel('/employe/plats');
        $platId = $this->id($id);

        if (($_POST['confirmer'] ?? '') !== '1') {
            Session::flash('danger', 'Cochez la case de confirmation pour supprimer ce plat.');
            Http::redirect('/employe/plats/' . $platId);
        }
        try {
            (new PlatAdminService())->delete($platId);
        } catch (CatalogException $e) {
            if ($e->raison === 'introuvable') {
                Http::notFound();
            }
            Session::flash('danger', $e->getMessage());
            Http::redirect('/employe/plats/' . $platId);
        }

        Session::flash('success', 'Le plat a été supprimé.');
        Http::redirect('/employe/plats');
    }

    // ---------- Thèmes et régimes ----------
    public function categories(): void
    {
        Auth::requireRole(self::ROLES);
        View::render('employe/categories', [
            'title' => 'Thèmes et régimes',
            'current' => '/employe',
            'themes' => (new ReferentielRepository('theme'))->all(),
            'regimes' => (new ReferentielRepository('regime'))->all(),
        ]);
    }

    public function categorieAjouter(string $type): void
    {
        $this->personnel('/employe/categories');
        $this->actionCategorie($type, static function (ReferentielService $s, string $libelle): void {
            $s->create($libelle);
        }, 'ajouté', $_POST['libelle'] ?? null);
    }

    public function categorieRenommer(string $type, string $id): void
    {
        $this->personnel('/employe/categories');
        $ref = $this->id($id);
        $this->actionCategorie($type, static function (ReferentielService $s, string $libelle) use ($ref): void {
            $s->rename($ref, $libelle);
        }, 'renommé', $_POST['libelle'] ?? null);
    }

    public function categorieSupprimer(string $type, string $id): void
    {
        $this->personnel('/employe/categories');
        $ref = $this->id($id);
        $this->actionCategorie($type, static function (ReferentielService $s) use ($ref): void {
            $s->delete($ref);
        }, 'supprimé', 'valide');
    }

    // ---------- Horaires ----------
    public function horaires(): void
    {
        Auth::requireRole(self::ROLES);
        $this->formulaireHoraires($this->horairesActuels(), [], 200);
    }

    public function horairesEnregistrer(): void
    {
        $this->personnel('/employe/horaires');
        $v = HoraireValidator::validate($_POST);

        if ($v['errors'] !== []) {
            $this->formulaireHoraires($v['old'], $v['errors'], 422);
            return;
        }

        (new HoraireAdminRepository())->saveAll($v['rows']);
        Session::flash('success', 'Les horaires ont été enregistrés : ils sont affichés en bas de chaque page du site.');
        Http::redirect('/employe/horaires');
    }

    // ---------- Outils ----------

    private function enregistrerPlat(?int $id): void
    {
        $this->personnel('/employe/plats');
        $repo = new PlatAdminRepository();
        if ($id !== null && $repo->find($id) === null) {
            Http::notFound();
        }

        $v = PlatValidator::validate($_POST, array_map('intval', array_column($repo->allergenes(), 'id')));
        if ($v['errors'] !== []) {
            $this->formulairePlat($id, ['titre' => $v['data']['titre'], 'type' => $v['data']['type'], 'allergenes' => $v['data']['allergene_ids']],
                $v['errors'], $id !== null ? $repo->find($id) : null, 422);
            return;
        }

        try {
            $platId = (new PlatAdminService())->save($id, $v['data']);
        } catch (CatalogException $e) {
            Http::notFound();
        }

        Session::flash('success', $id === null ? 'Le plat a été créé.' : 'Le plat a été enregistré.');
        Http::redirect('/employe/plats/' . $platId);
    }

    private function formulairePlat(?int $id, array $old, array $errors, ?array $plat = null, int $statut = 200): void
    {
        http_response_code($statut);
        View::render('employe/plat_form', [
            'title' => $id === null ? 'Nouveau plat' : 'Modifier le plat',
            'current' => '/employe',
            'id' => $id,
            'old' => $old,
            'errors' => $errors,
            'allergenes' => (new PlatAdminRepository())->allergenes(),
            'plat' => $plat,
        ]);
    }

    private function actionCategorie(string $type, callable $action, string $verbe, mixed $valeur): void
    {
        if (!isset(ReferentielRepository::TABLES[$type])) {
            Http::notFound();
        }

        $v = ReferentielValidator::validate($valeur === 'valide' ? 'valide' : $valeur);
        if ($v['error'] !== null) {
            Session::flash('danger', $v['error']);
            Http::redirect('/employe/categories');
        }

        try {
            $action(new ReferentielService($type), $v['libelle']);
        } catch (CatalogException $e) {
            if ($e->raison === 'introuvable') {
                Http::notFound();
            }
            Session::flash('danger', $e->getMessage());
            Http::redirect('/employe/categories');
        }

        Session::flash('success', 'L\'élément a été ' . $verbe . '.');
        Http::redirect('/employe/categories');
    }

    /** @return array<string,string> valeurs actuelles, au format du formulaire */
    private function horairesActuels(): array
    {
        $valeurs = [];
        for ($j = 1; $j <= 7; $j++) {
            $valeurs["ferme_$j"] = '';
            $valeurs["ouverture_$j"] = '';
            $valeurs["fermeture_$j"] = '';
        }
        foreach ((new HoraireAdminRepository())->all() as $h) {
            $j = (int) $h['jour'];
            $valeurs["ferme_$j"] = (int) $h['ferme'] === 1 ? '1' : '';
            $valeurs["ouverture_$j"] = $h['heure_ouverture'] !== null ? substr((string) $h['heure_ouverture'], 0, 5) : '';
            $valeurs["fermeture_$j"] = $h['heure_fermeture'] !== null ? substr((string) $h['heure_fermeture'], 0, 5) : '';
        }

        return $valeurs;
    }

    private function formulaireHoraires(array $valeurs, array $errors, int $statut): void
    {
        http_response_code($statut);
        View::render('employe/horaires', [
            'title' => 'Horaires',
            'current' => '/employe',
            'valeurs' => $valeurs,
            'errors' => $errors,
        ]);
    }

    private function id(string $valeur): int
    {
        if (!preg_match('/^[1-9]\d{0,9}$/', $valeur)) {
            Http::notFound();
        }

        return (int) $valeur;
    }

    private function personnel(string $retour): array
    {
        if (Auth::user() === null) {
            Session::flash('info', 'Votre session a expiré. Veuillez vous reconnecter.');
            Http::redirect('/connexion?redirect=' . rawurlencode($retour));
        }

        return Auth::requireRole(self::ROLES);
    }
}
