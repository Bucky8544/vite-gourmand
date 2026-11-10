<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Auth;
use App\Core\Http;
use App\Core\Session;
use App\Core\View;
use App\Repository\ReviewRepository;
use App\Service\OrderException;
use App\Service\ReviewModerationService;

/** Modération des avis clients par les employés et l'administrateur. */
final class StaffReviewController
{
    private const ROLES = ['employe', 'administrateur'];

    public function index(): void
    {
        $user = Auth::requireRole(self::ROLES);

        $statut = is_string($_GET['statut'] ?? null) && isset(ReviewRepository::STATUTS[$_GET['statut']]) ? $_GET['statut'] : 'en_attente';
        $page = is_string($_GET['page'] ?? null) && preg_match('/^[1-9]\d{0,3}$/', $_GET['page']) ? (int) $_GET['page'] : 1;

        $repo = new ReviewRepository();
        $resultat = $repo->search($statut, $page);

        View::render('employe/avis', [
            'title' => 'Avis clients',
            'current' => '/employe',
            'user' => $user,
            'lignes' => $resultat['lignes'],
            'total' => $resultat['total'],
            'page' => $page,
            'pages' => max(1, (int) ceil($resultat['total'] / ReviewRepository::PAR_PAGE)),
            'statut' => $statut,
            'compte' => $repo->countByStatus(),
        ]);
    }

    public function decider(string $id): void
    {
        $personnel = $this->personnel();
        $onglet = is_string($_POST['onglet'] ?? null) && isset(ReviewRepository::STATUTS[$_POST['onglet']]) ? $_POST['onglet'] : 'en_attente';
        $retour = '/employe/avis?statut=' . $onglet;

        if (!preg_match('/^[1-9]\d{0,9}$/', $id)) {
            Http::notFound();
        }
        $decision = is_string($_POST['decision'] ?? null) ? $_POST['decision'] : '';

        try {
            (new ReviewModerationService())->decide((int) $personnel['id'], (int) $id, $decision);
        } catch (OrderException $e) {
            Session::flash('danger', $e->getMessage());
            Http::redirect($retour);
        }

        Session::flash('success', $decision === 'valide'
            ? "L'avis est validé : il apparaît sur la page d'accueil."
            : "L'avis est refusé : il n'apparaît pas sur le site.");
        Http::redirect($retour);
    }

    /** Les POST ne gardent pas de destination : une session expirée renvoie vers l'espace. */
    private function personnel(): array
    {
        if (Auth::user() === null) {
            Session::flash('info', 'Votre session a expiré. Veuillez vous reconnecter.');
            Http::redirect('/connexion?redirect=' . rawurlencode('/employe/avis'));
        }

        return Auth::requireRole(self::ROLES);
    }
}
