<?php
declare(strict_types=1);

namespace App\Service;

use App\Core\ClientIp;
use DateTimeImmutable;

/** Limite les échecs de connexion : par couple (e-mail, adresse) et par adresse. */
final class LoginThrottle
{
    public const FENETRE = 900;      // 15 minutes
    public const MAX_COUPLE = 5;     // échecs pour un même e-mail depuis une même adresse
    public const MAX_ADRESSE = 20;   // échecs, tous e-mails confondus, depuis une même adresse

    private RateLimiter $limiteur;

    public function __construct(?RateLimiter $limiteur = null)
    {
        $this->limiteur = $limiteur ?? new RateLimiter();
    }

    public static function cleCouple(string $email, string $ip): string
    {
        return RateLimiter::cle('connexion', mb_strtolower(trim($email)), ClientIp::groupe($ip));
    }

    public static function cleAdresse(string $ip): string
    {
        return RateLimiter::cle('adresse', ClientIp::groupe($ip));
    }

    /** @return int|null secondes à attendre, ou null si la tentative est autorisée */
    public function attente(string $email, string $ip, ?DateTimeImmutable $now = null): ?int
    {
        $now ??= RateLimiter::maintenant();

        return self::decision(
            $this->limiteur->etat('connexion_couple', self::cleCouple($email, $ip), self::FENETRE, $now),
            $this->limiteur->etat('connexion_adresse', self::cleAdresse($ip), self::FENETRE, $now),
            $now
        );
    }

    public function echec(string $email, string $ip, ?DateTimeImmutable $now = null): void
    {
        $this->limiteur->enregistrer('connexion_couple', self::cleCouple($email, $ip), $now);
        $this->limiteur->enregistrer('connexion_adresse', self::cleAdresse($ip), $now);
    }

    /** Une connexion réussie efface les échecs du couple (le compteur de l'adresse reste). */
    public function succes(string $email, string $ip): void
    {
        $this->limiteur->oublier('connexion_couple', self::cleCouple($email, $ip));
    }

    /**
     * @param array{nombre:int,plus_ancien:?DateTimeImmutable} $couple
     * @param array{nombre:int,plus_ancien:?DateTimeImmutable} $adresse
     */
    public static function decision(array $couple, array $adresse, DateTimeImmutable $now): ?int
    {
        $attentes = [];
        foreach ([[$couple, self::MAX_COUPLE], [$adresse, self::MAX_ADRESSE]] as [$etat, $max]) {
            if ($etat['nombre'] >= $max) {
                $ecoule = $etat['plus_ancien'] !== null ? $now->getTimestamp() - $etat['plus_ancien']->getTimestamp() : 0;
                $attentes[] = max(1, self::FENETRE - $ecoule);
            }
        }

        return $attentes === [] ? null : max($attentes);
    }
}
