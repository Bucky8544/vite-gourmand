<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Database;
use MongoDB\Client;
use Throwable;

/** Vérifie l'état des deux bases (utile aussi pour tester le déploiement). */
final class HealthController
{
    public function index(): void
    {
        $etat = ['mysql' => 'ko', 'mongo' => 'ko'];

        try {
            Database::pdo()->query('SELECT 1');
            $etat['mysql'] = 'ok';
        } catch (Throwable $e) {
            error_log('Health MySQL : ' . $e->getMessage());
        }

        try {
            (new Client(getenv('MONGO_URI') ?: 'mongodb://mongo:27017'))
                ->selectDatabase('admin')->command(['ping' => 1]);
            $etat['mongo'] = 'ok';
        } catch (Throwable $e) {
            error_log('Health MongoDB : ' . $e->getMessage());
        }

        http_response_code(in_array('ko', $etat, true) ? 503 : 200);
        header('Content-Type: application/json');
        echo json_encode($etat);
    }
}
