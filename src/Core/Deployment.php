<?php
declare(strict_types=1);

namespace App\Core;

/** Réglages propres à l'hébergement (derrière le routeur de Heroku) : HTTPS, en-têtes, indexation. */
final class Deployment
{
    /** Vrai si la requête est en HTTPS, directement ou derrière un routeur qui l'indique (X-Forwarded-Proto). */
    public static function estHttps(array $server): bool
    {
        $https = strtolower((string) ($server['HTTPS'] ?? ''));
        if ($https !== '' && $https !== 'off') {
            return true;
        }

        return strtolower(trim(explode(',', (string) ($server['HTTP_X_FORWARDED_PROTO'] ?? ''))[0])) === 'https';
    }

    /** Adresse HTTPS vers laquelle rediriger une requête HTTP en production, sinon null. */
    public static function redirectionHttps(array $server, string $env, string $appUrl): ?string
    {
        if ($env !== 'prod' || self::estHttps($server)) {
            return null;
        }
        $hote = parse_url($appUrl, PHP_URL_HOST);
        if (!is_string($hote) || $hote === '') {
            return null; // sans adresse officielle, on ne redirige jamais vers un hôte fourni par le visiteur
        }
        $uri = (string) ($server['REQUEST_URI'] ?? '/');
        if ($uri === '' || $uri[0] !== '/' || str_starts_with($uri, '//')) {
            $uri = '/';
        }

        return 'https://' . $hote . $uri;
    }

    /** @return list<string> */
    public static function entetes(string $env, bool $https, bool $noindex): array
    {
        $entetes = [];
        if ($env === 'prod' && $https) {
            $entetes[] = 'Strict-Transport-Security: max-age=15552000';
        }
        if ($noindex) {
            $entetes[] = 'X-Robots-Tag: noindex, nofollow';
        }

        return $entetes;
    }

    /** Appliqué au début de chaque requête web (jamais en ligne de commande). */
    public static function appliquer(array &$server): void
    {
        $env = (string) (getenv('APP_ENV') ?: 'dev');
        if (self::estHttps($server)) {
            $server['HTTPS'] = 'on'; // la session s'appuie sur cette valeur pour le cookie sécurisé
        }

        $cible = self::redirectionHttps($server, $env, (string) getenv('APP_URL'));
        if ($cible !== null) {
            header('Location: ' . $cible, true, 301);
            exit;
        }

        foreach (self::entetes($env, self::estHttps($server), getenv('ROBOTS_NOINDEX') === '1') as $entete) {
            header($entete);
        }
        if (getenv('APP_DEBUG') !== '1') {
            ini_set('display_errors', '0');
        }
    }
}
