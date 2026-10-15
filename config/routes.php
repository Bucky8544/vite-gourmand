<?php
declare(strict_types=1);

use App\Controller\AuthController;
use App\Controller\HealthController;
use App\Controller\HomeController;
use App\Controller\SpaceController;
use App\Core\Router;

return static function (Router $r): void {
    $r->get('/', [HomeController::class, 'index']);
    $r->get('/sante', [HealthController::class, 'index']);

    $r->get('/inscription', [AuthController::class, 'showRegister']);
    $r->post('/inscription', [AuthController::class, 'register']);
    $r->get('/connexion', [AuthController::class, 'showLogin']);
    $r->post('/connexion', [AuthController::class, 'login']);
    $r->post('/deconnexion', [AuthController::class, 'logout']);

    $r->get('/mon-espace', [SpaceController::class, 'utilisateur']);
    $r->get('/employe', [SpaceController::class, 'employe']);
    $r->get('/admin', [SpaceController::class, 'admin']);
};
