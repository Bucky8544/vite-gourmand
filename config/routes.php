<?php
declare(strict_types=1);

use App\Controller\AuthController;
use App\Controller\HealthController;
use App\Controller\HomeController;
use App\Controller\MenuController;
use App\Controller\OrderController;
use App\Controller\PasswordController;
use App\Controller\SpaceController;
use App\Controller\UserSpaceController;
use App\Core\Router;

return static function (Router $r): void {
    $r->get('/', [HomeController::class, 'index']);
    $r->get('/sante', [HealthController::class, 'index']);

    $r->get('/menus', [MenuController::class, 'index']);
    $r->get('/menus/{id}', [MenuController::class, 'show']);
    $r->get('/api/menus', [MenuController::class, 'api']);

    $r->get('/commande', [OrderController::class, 'create']);
    $r->post('/commande/recapitulatif', [OrderController::class, 'recap']);
    $r->post('/commande/modifier', [OrderController::class, 'modifier']);
    $r->post('/commande', [OrderController::class, 'store']);
    $r->get('/commande/confirmation/{numero}', [OrderController::class, 'confirmation']);
    $r->get('/api/commande/prix', [OrderController::class, 'price']);

    $r->get('/inscription', [AuthController::class, 'showRegister']);
    $r->post('/inscription', [AuthController::class, 'register']);
    $r->get('/connexion', [AuthController::class, 'showLogin']);
    $r->post('/connexion', [AuthController::class, 'login']);
    $r->post('/deconnexion', [AuthController::class, 'logout']);

    $r->get('/mot-de-passe-oublie', [PasswordController::class, 'showForgot']);
    $r->post('/mot-de-passe-oublie', [PasswordController::class, 'forgot']);
    $r->get('/reinitialisation/{token}', [PasswordController::class, 'showReset']);
    $r->post('/reinitialisation/{token}', [PasswordController::class, 'reset']);

    // Espace utilisateur
    $r->get('/mon-espace', [UserSpaceController::class, 'index']);
    $r->get('/mon-espace/profil', [UserSpaceController::class, 'profil']);
    $r->post('/mon-espace/profil', [UserSpaceController::class, 'profilEnregistrer']);
    $r->get('/mon-espace/commandes/{numero}', [UserSpaceController::class, 'show']);
    $r->get('/mon-espace/commandes/{numero}/modifier', [UserSpaceController::class, 'modifierForm']);
    $r->post('/mon-espace/commandes/{numero}/modifier', [UserSpaceController::class, 'modifierRecap']);
    $r->post('/mon-espace/commandes/{numero}/modifier/retour', [UserSpaceController::class, 'modifierRetour']);
    $r->post('/mon-espace/commandes/{numero}/modifier/confirmer', [UserSpaceController::class, 'modifierConfirmer']);
    $r->get('/mon-espace/commandes/{numero}/annuler', [UserSpaceController::class, 'annulerForm']);
    $r->post('/mon-espace/commandes/{numero}/annuler', [UserSpaceController::class, 'annuler']);
    $r->post('/mon-espace/commandes/{numero}/avis', [UserSpaceController::class, 'avis']);

    $r->get('/employe', [SpaceController::class, 'employe']);
    $r->get('/admin', [SpaceController::class, 'admin']);
};
