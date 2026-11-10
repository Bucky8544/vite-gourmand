<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Csrf;
use App\Core\Router;
use App\Core\Session;
use App\Core\View;

// En-têtes de sécurité
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");

// Aucune erreur affichée au visiteur : on journalise et on affiche une page neutre
ini_set('display_errors', '0');
set_error_handler(static function (int $no, string $msg, string $file, int $line): bool {
    if (!(error_reporting() & $no)) {
        return false;
    }
    throw new ErrorException($msg, 0, $no, $file, $line);
});
set_exception_handler(static function (Throwable $e): void {
    error_log((string) $e);
    http_response_code(500);
    View::render('errors/500', [
        'title' => 'Erreur serveur',
        'debug' => getenv('APP_DEBUG') === '1' ? (string) $e : null,
    ]);
});

Session::start();

// Protection CSRF sur toute requête qui modifie des données
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
    $token = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
    if (!Csrf::verify(is_string($token) ? $token : null)) {
        http_response_code(403);
        View::render('errors/403', ['title' => 'Accès refusé']);
        exit;
    }
}

$router = new Router();
(require __DIR__ . '/../config/routes.php')($router);
$router->dispatch($method, $_SERVER['REQUEST_URI'] ?? '/');
