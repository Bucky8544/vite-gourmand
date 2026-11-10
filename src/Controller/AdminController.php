<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Auth;
use App\Core\Http;
use App\Core\Money;
use App\Core\Session;
use App\Core\View;
use App\Repository\EmployeeAdminRepository;
use App\Repository\MenuAdminRepository;
use App\Service\AdminStatsBuilder;
use App\Service\BarChart;
use App\Service\CatalogException;
use App\Service\EmployeeAdminService;
use App\Service\StaffAccountMailer;
use App\Service\StatsRepository;
use App\Service\StatsSyncService;
use App\Validation\AdminStatsFilter;
use App\Validation\EmployeeValidator;
use DateTimeImmutable;
use Throwable;

/** Espace administrateur : statistiques et comptes employés. Les employés n'y ont pas accès (403). */
final class AdminController
{
    private const ROLES = ['administrateur'];

    // ---------- Statistiques ----------
    public function index(): void
    {
        $user = Auth::requireRole(self::ROLES);
        $f = AdminStatsFilter::parse($_GET);
        $menus = (new MenuAdminRepository())->listAll();

        $lignes = [];
        $total = ['nombre_commandes' => 0, 'chiffre_affaires_centimes' => 0];
        $indisponible = false;

        if ($f['errors'] === []) {
            try {
                $stats = StatsRepository::fromEnv();
                $lignes = AdminStatsBuilder::merge($menus, $stats->commandesParMenu($f['debut'], $f['fin']));
                $total = $stats->chiffreAffaires($f['menu'], $f['debut'], $f['fin']);
            } catch (Throwable $e) {
                error_log('Statistiques indisponibles : ' . $e->getMessage());
                $indisponible = true;
            }
        }

        $periode = $f['errors'] === [] ? $this->libellePeriode($f['du'], $f['au']) : 'Période non valide';
        $parCa = $lignes;
        usort($parCa, static fn (array $a, array $b): int => ($b['ca_centimes'] <=> $a['ca_centimes']) ?: strcmp($a['titre'], $b['titre']));

        View::render('admin/index', [
            'title' => 'Espace administrateur',
            'current' => '/admin',
            'user' => $user,
            'f' => $f,
            'menus' => $menus,
            'lignes' => $lignes,
            'total' => $total,
            'indisponible' => $indisponible,
            'periode' => $periode,
            'graphCommandes' => BarChart::svg(
                array_map(static fn (array $l): array => ['label' => $l['titre'], 'valeur' => $l['commandes'], 'texte' => (string) $l['commandes']], $lignes),
                'Nombre de commandes par menu', "Comparaison du nombre de commandes acceptées par menu. $periode.", 'commandes'
            ),
            'graphCa' => BarChart::svg(
                array_map(static fn (array $l): array => ['label' => $l['titre'], 'valeur' => $l['ca_centimes'], 'texte' => Money::format($l['ca_centimes'])], $parCa),
                "Chiffre d'affaires par menu", "Comparaison du chiffre d'affaires par menu, livraison comprise. $periode.", 'ca'
            ),
        ]);
    }

    public function resynchroniser(): void
    {
        $this->administrateur();

        try {
            $n = (new StatsSyncService())->resync();
            Session::flash('success', "Les statistiques ont été reconstruites : $n commande(s) comptabilisée(s).");
        } catch (Throwable $e) {
            error_log('Resynchronisation des statistiques : ' . $e->getMessage());
            Session::flash('danger', 'La reconstruction a échoué : la base MongoDB est peut-être indisponible. Réessayez dans un instant.');
        }

        Http::redirect('/admin');
    }

    // ---------- Comptes employés ----------
    public function employes(): void
    {
        Auth::requireRole(self::ROLES);
        $this->afficherEmployes([], []);
    }

    public function employeCreer(): void
    {
        $this->administrateur();
        $v = EmployeeValidator::validate($_POST);

        if ($v['errors'] !== []) {
            $this->afficherEmployes($v['errors'], ['email' => $v['data']['email']], 422);
            return;
        }

        try {
            (new EmployeeAdminService())->create($v['data']['email'], $v['data']['password']);
        } catch (CatalogException $e) {
            $this->afficherEmployes(['email' => $e->getMessage()], ['email' => $v['data']['email']], 422);
            return;
        }

        try {
            (new StaffAccountMailer())->sendAccountCreated($v['data']['email']);
            Session::flash('success', 'Le compte de ' . $v['data']['email'] . " a été créé. Un e-mail l'en informe, sans le mot de passe : "
                . "communiquez-le-lui vous-même.");
        } catch (Throwable $e) {
            error_log("Mail de création de compte employé non envoyé : " . $e->getMessage());
            Session::flash('info', 'Le compte de ' . $v['data']['email'] . " a été créé, mais l'e-mail n'a pas pu être envoyé : "
                . 'prévenez l\'employé directement et communiquez-lui son mot de passe.');
        }

        Http::redirect('/admin/employes');
    }

    public function employeBasculer(string $id): void
    {
        $this->administrateur();
        if (!preg_match('/^[1-9]\d{0,9}$/', $id)) {
            Http::notFound();
        }
        $actif = ($_POST['actif'] ?? '') === '1';

        try {
            (new EmployeeAdminService())->setActive((int) $id, $actif);
        } catch (CatalogException $e) {
            if ($e->raison === 'introuvable') {
                Http::notFound();
            }
            Session::flash('danger', $e->getMessage());
            Http::redirect('/admin/employes');
        }

        Session::flash('success', $actif
            ? 'Le compte est de nouveau utilisable.'
            : "Le compte est désactivé : l'employé ne peut plus se connecter, et sa session en cours est fermée.");
        Http::redirect('/admin/employes');
    }

    // ---------- Outils ----------

    private function afficherEmployes(array $errors, array $old, int $statut = 200): void
    {
        http_response_code($statut);
        View::render('admin/employes', [
            'title' => 'Comptes employés',
            'current' => '/admin',
            'employes' => (new EmployeeAdminRepository())->all(),
            'errors' => $errors,
            'old' => $old,
        ]);
    }

    private function libellePeriode(string $du, string $au): string
    {
        $format = static fn (string $d): string => (new DateTimeImmutable($d))->format('d/m/Y');

        return match (true) {
            $du !== '' && $au !== '' => 'Du ' . $format($du) . ' au ' . $format($au),
            $du !== '' => 'Depuis le ' . $format($du),
            $au !== '' => "Jusqu'au " . $format($au),
            default => 'Toutes les dates',
        };
    }

    /** Les POST ne gardent pas de destination : une session expirée renvoie vers l'espace. */
    private function administrateur(): array
    {
        if (Auth::user() === null) {
            Session::flash('info', 'Votre session a expiré. Veuillez vous reconnecter.');
            Http::redirect('/connexion?redirect=' . rawurlencode('/admin'));
        }

        return Auth::requireRole(self::ROLES);
    }
}
