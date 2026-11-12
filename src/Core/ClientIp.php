<?php
declare(strict_types=1);

namespace App\Core;

final class ClientIp
{
    /**
     * Adresse du visiteur. Derrière le routeur de Heroku, REMOTE_ADDR est celle du routeur : la vraie adresse est la
     * DERNIÈRE valeur de X-Forwarded-For (le routeur l'ajoute ; les valeurs précédentes peuvent être inventées par le visiteur).
     */
    public static function detect(array $server, bool $proxyDeConfiance): string
    {
        if ($proxyDeConfiance) {
            $liste = explode(',', (string) ($server['HTTP_X_FORWARDED_FOR'] ?? ''));
            $derniere = trim((string) end($liste));
            if (filter_var($derniere, FILTER_VALIDATE_IP) !== false) {
                return $derniere;
            }
        }

        $ip = (string) ($server['REMOTE_ADDR'] ?? '');

        return filter_var($ip, FILTER_VALIDATE_IP) !== false ? $ip : '0.0.0.0';
    }

    public static function courante(): string
    {
        return self::detect($_SERVER, getenv('APP_ENV') === 'prod');
    }

    /**
     * Clé de regroupement : une IPv4 telle quelle, une IPv6 réduite à son préfixe /64 (un visiteur en possède des milliards).
     * Une IPv4 écrite en IPv6 (::ffff:a.b.c.d) redevient une IPv4.
     */
    public static function groupe(string $ip): string
    {
        $binaire = @inet_pton($ip);
        if ($binaire === false || strlen($binaire) !== 16) {
            return $ip;
        }
        if (substr($binaire, 0, 12) === str_repeat("\0", 10) . "\xff\xff") {
            return implode('.', array_map('ord', str_split(substr($binaire, 12))));
        }

        return '6:' . bin2hex(substr($binaire, 0, 8));
    }
}
