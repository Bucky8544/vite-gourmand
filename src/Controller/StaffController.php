<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Auth;
use App\Core\Http;
use App\Core\Session;
use App\Core\View;
use App\Repository\OrderRepository;
use App\Repository\ReviewRepository;
use App\Repository\StaffOrderRepository;
use App\Service\OrderException;
use App\Service\OrderStaffEffects;
use App\Service\OrderStaffService;
use App\Service\OrderStatus;
use App\Validation\CancelValidator;

/** Espace des employés et de l'administrateur : gestion des commandes. */
final class StaffController
{
    private const ROLES = ['employe', 'administrateur'];

    public function index(): void
    {
        $user = Auth::requireRole(self::ROLES);

        $statut = is_string($_GET['statut'] ?? null) && isset(OrderStatus::LABELS[$_GET['statut']]) ? $_GET['statut'] : '';
        $q = is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']), 0, 100) : '';
        $page = is_string($_GET['page'] ?? null) && preg_match('/^[1-9]\d{0,3}$/', $_GET['page']) ? (int) $_GET['page'] : 1;

        $repo = new StaffOrderRepository();
        $resultat = $repo->search(['statut' => $statut, 'q' => $q], $page);

        View::render('employe/index', [
            'title' => 'Espace employé',
            'current' => '/employe',
            'user' => $user,
            'lignes' => $resultat['lignes'],
            'total' => $resultat['total'],
            'page' => $page,
            'pages' => max(1, (int) ceil($resultat['total'] / StaffOrderRepository::PAR_PAGE)),
            'statut' => $statut,
            'q' => $q,
            'compte' => $repo->countsByStatus(),
            'avisEnAttente' => (new ReviewRepository())->countByStatus()['en_attente'] ?? 0,
        ]);
    }

    public function show(string $numero): void
    {
        Auth::requireRole(self::ROLES);
        $this->afficher($this->commande($numero), []);
    }

    public function changerStatut(string $numero): void
    {
        $personnel = $this->personnel();
        $this->commande($numero); // 404 si inconnue

        $cible = is_string($_POST['statut'] ?? null) ? $_POST['statut'] : '';
        $pretMateriel = ($_POST['pret_materiel'] ?? '') === '1';

        try {
            (new OrderStaffService())->changeStatus((int) $personnel['id'], $numero, $cible, $pretMateriel);
        } catch (OrderException $e) {
            Session::flash('danger', $e->getMessage());
            Http::redirect('/employe/commandes/' . $numero);
        }

        OrderStaffEffects::fromEnv()->apply($this->commande($numero), $cible);
        Session::flash('success', 'La commande est maintenant « ' . OrderStatus::label($cible) . ' ».');
        Http::redirect('/employe/commandes/' . $numero);
    }

    public function annuler(string $numero): void
    {
        $personnel = $this->personnel();
        $commande = $this->commande($numero);

        $v = CancelValidator::validate($_POST);
        if ($v['errors'] !== []) {
            $this->afficher($commande, $v['errors'], $v['old'], 422);
            return;
        }

        try {
            (new OrderStaffService())->cancel((int) $personnel['id'], $numero, $v['data']['mode_contact'], $v['data']['motif']);
        } catch (OrderException $e) {
            Session::flash('danger', $e->getMessage());
            Http::redirect('/employe/commandes/' . $numero);
        }

        OrderStaffEffects::fromEnv()->apply($this->commande($numero), 'annulee');
        Session::flash('success', 'La commande ' . $numero . ' a été annulée. Un e-mail en informe le client.');
        Http::redirect('/employe/commandes/' . $numero);
    }

    // ---------- Outils ----------

    /** Les POST ne gardent pas de destination : une session expirée renvoie vers l'espace. */
    private function personnel(): array
    {
        if (Auth::user() === null) {
            Session::flash('info', 'Votre session a expiré. Veuillez vous reconnecter.');
            Http::redirect('/connexion?redirect=' . rawurlencode('/employe'));
        }

        return Auth::requireRole(self::ROLES);
    }

    private function commande(string $numero): array
    {
        if (!preg_match('/^VG-\d{4}-\d{6}$/', $numero)) {
            Http::notFound();
        }
        $commande = (new StaffOrderRepository())->find($numero);
        if ($commande === null) {
            Http::notFound();
        }

        return $commande;
    }

    private function afficher(array $c, array $errors, array $old = [], int $statut = 200): void
    {
        http_response_code($statut);
        View::render('employe/commande', [
            'title' => 'Commande ' . $c['numero_commande'],
            'current' => '/employe',
            'commande' => $c,
            'historique' => (new StaffOrderRepository())->history((int) $c['id']),
            'avis' => (new OrderRepository())->avis((int) $c['id']),
            'suivants' => OrderStatus::suivants((string) $c['statut'], (bool) $c['pret_materiel']),
            'annulable' => OrderStatus::annulable((string) $c['statut']),
            'errors' => $errors,
            'old' => $old,
        ]);
    }
}
