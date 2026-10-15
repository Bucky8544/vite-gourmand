<?php
declare(strict_types=1);

use App\Controller\AuthController;
use App\Controller\HealthController;
use App\Controller\HomeController;
use App\Core\Router;

return static function (Router $r): void {
    $r->get('/', [HomeController::class, 'index']);
    $r->get('/sante', [HealthController::class, 'index']);

    $r->get('/inscription', [AuthController::class, 'showRegister']);
    $r->post('/inscription', [AuthController::class, 'register']);
};
