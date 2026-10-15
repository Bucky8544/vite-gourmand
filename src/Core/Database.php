<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                getenv('MYSQL_HOST') ?: 'mysql',
                getenv('MYSQL_PORT') ?: '3306',
                getenv('MYSQL_DATABASE')
            );
            self::$pdo = new PDO($dsn, (string) getenv('MYSQL_USER'), (string) getenv('MYSQL_PASSWORD'), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false, // vraies requêtes préparées
            ]);
        }

        return self::$pdo;
    }
}
