<?php
declare(strict_types=1);

use App\Controller\HealthController;
use App\Controller\HomeController;
use App\Core\Router;

return static function (Router $r): void {
    $r->get('/', [HomeController::class, 'index']);
    $r->get('/sante', [HealthController::class, 'index']);
};
