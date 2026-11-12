<?php
declare(strict_types=1);

namespace App\Service;

use App\Core\Database;
use DateTimeImmutable;
use DateTimeZone;
use PDO;

/** Compte les événements récents (échecs de connexion, inscriptions) par clé, dans la base de données. Heures en UTC. */
final class RateLimiter
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::pdo();
    }

    public static function maintenant(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }

    /** Empreinte (HMAC) d'un identifiant : la base ne contient jamais d'e-mail ni d'adresse en clair. */
    public static function cle(string ...$parties): string
    {
        return hash_hmac('sha256', implode('|', $parties), (string) (getenv('APP_KEY') ?: 'vite-gourmand'));
    }

    /** @return array{nombre:int,plus_ancien:?DateTimeImmutable} */
    public function etat(string $type, string $cle, int $fenetre, ?DateTimeImmutable $now = null): array
    {
        $now ??= self::maintenant();
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) AS n, MIN(created_at) AS premier FROM limitation_acces
             WHERE type = :t AND cle = :c AND created_at >= :depuis'
        );
        $stmt->execute(['t' => $type, 'c' => $cle, 'depuis' => $now->modify("-$fenetre seconds")->format('Y-m-d H:i:s')]);
        $ligne = $stmt->fetch();

        return [
            'nombre' => (int) $ligne['n'],
            'plus_ancien' => $ligne['premier'] !== null ? new DateTimeImmutable((string) $ligne['premier'], new DateTimeZone('UTC')) : null,
        ];
    }

    public function enregistrer(string $type, string $cle, ?DateTimeImmutable $now = null): void
    {
        $now ??= self::maintenant();
        $this->pdo->prepare('INSERT INTO limitation_acces (type, cle, created_at) VALUES (:t, :c, :d)')
            ->execute(['t' => $type, 'c' => $cle, 'd' => $now->format('Y-m-d H:i:s')]);

        if (random_int(1, 50) === 1) { // ménage de temps en temps : la table reste minuscule
            $this->purger($now);
        }
    }

    public function oublier(string $type, string $cle): void
    {
        $this->pdo->prepare('DELETE FROM limitation_acces WHERE type = :t AND cle = :c')->execute(['t' => $type, 'c' => $cle]);
    }

    /** Supprime les événements de plus de 24 heures (par défaut). */
    public function purger(?DateTimeImmutable $now = null, int $conserver = 86400): void
    {
        $now ??= self::maintenant();
        $this->pdo->prepare('DELETE FROM limitation_acces WHERE created_at < :limite')
            ->execute(['limite' => $now->modify("-$conserver seconds")->format('Y-m-d H:i:s')]);
    }
}
