<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;

final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $c = self::config();
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $c['host'], $c['port'], $c['name']);
            self::$pdo = new PDO($dsn, $c['user'], $c['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false, // vraies requêtes préparées
            ]);
        }

        return self::$pdo;
    }

    /**
     * Paramètres de connexion : JAWSDB_URL (hébergement) si elle existe, sinon les variables MYSQL_* (Docker local).
     *
     * @return array{host:string,port:string,name:string,user:string,pass:string}
     */
    public static function config(): array
    {
        $url = (string) getenv('JAWSDB_URL');
        if ($url !== '') {
            return self::depuisUrl($url);
        }

        return [
            'host' => (string) (getenv('MYSQL_HOST') ?: 'mysql'),
            'port' => (string) (getenv('MYSQL_PORT') ?: '3306'),
            'name' => (string) getenv('MYSQL_DATABASE'),
            'user' => (string) getenv('MYSQL_USER'),
            'pass' => (string) getenv('MYSQL_PASSWORD'),
        ];
    }

    /**
     * Décompose une adresse mysql://utilisateur:mot_de_passe@hote:port/base.
     *
     * @return array{host:string,port:string,name:string,user:string,pass:string}
     */
    public static function depuisUrl(string $url): array
    {
        $p = parse_url($url);
        if ($p === false || ($p['scheme'] ?? '') !== 'mysql' || empty($p['host']) || empty($p['user'])
            || !isset($p['path']) || trim($p['path'], '/') === '') {
            // Le message ne reprend jamais l'adresse : elle contient le mot de passe
            throw new RuntimeException("L'adresse de la base de données (JAWSDB_URL) n'est pas valide.");
        }

        return [
            'host' => $p['host'],
            'port' => (string) ($p['port'] ?? 3306),
            'name' => rawurldecode(trim($p['path'], '/')),
            'user' => rawurldecode($p['user']),
            'pass' => rawurldecode($p['pass'] ?? ''),
        ];
    }
}
