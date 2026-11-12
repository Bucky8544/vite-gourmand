<?php
declare(strict_types=1);

namespace App\Service;

use App\Core\ClientIp;
use DateTimeImmutable;

/** Limite les inscriptions depuis une même adresse : chaque inscription envoie un e-mail (quota du fournisseur). */
final class SignupThrottle
{
    public const FENETRE = 3600; // 1 heure
    public const MAX = 10;

    private RateLimiter $limiteur;

    public function __construct(?RateLimiter $limiteur = null)
    {
        $this->limiteur = $limiteur ?? new RateLimiter();
    }

    public static function cle(string $ip): string
    {
        return RateLimiter::cle('inscription', ClientIp::groupe($ip));
    }

    /** @return int|null secondes à attendre, ou null si l'inscription est autorisée */
    public function attente(string $ip, ?DateTimeImmutable $now = null): ?int
    {
        $now ??= RateLimiter::maintenant();

        return self::decision($this->limiteur->etat('inscription', self::cle($ip), self::FENETRE, $now), $now);
    }

    public function enregistrer(string $ip, ?DateTimeImmutable $now = null): void
    {
        $this->limiteur->enregistrer('inscription', self::cle($ip), $now);
    }

    /** @param array{nombre:int,plus_ancien:?DateTimeImmutable} $etat */
    public static function decision(array $etat, DateTimeImmutable $now): ?int
    {
        if ($etat['nombre'] < self::MAX) {
            return null;
        }
        $ecoule = $etat['plus_ancien'] !== null ? $now->getTimestamp() - $etat['plus_ancien']->getTimestamp() : 0;

        return max(1, self::FENETRE - $ecoule);
    }
}
