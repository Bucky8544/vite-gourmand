<?php
declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;

final class PasswordResetRepository
{
    /** L'expiration est calculée par MySQL (NOW()) : aucun décalage de fuseau entre PHP et la base. */
    public function create(int $userId, string $tokenHash, int $ttlMinutes): void
    {
        $sql = 'INSERT INTO password_reset_token (utilisateur_id, token_hash, expires_at)
                VALUES (:u, :h, DATE_ADD(NOW(), INTERVAL ' . (int) $ttlMinutes . ' MINUTE))';
        Database::pdo()->prepare($sql)->execute(['u' => $userId, 'h' => $tokenHash]);
    }

    /** Jetons émis pendant la durée de validité (l'expiration vaut création + durée), utilisés ou non. */
    public function countRecent(int $userId): int
    {
        $stmt = Database::pdo()->prepare(
            'SELECT COUNT(*) FROM password_reset_token WHERE utilisateur_id = :u AND expires_at > NOW()'
        );
        $stmt->execute(['u' => $userId]);

        return (int) $stmt->fetchColumn();
    }

    /** Jeton non utilisé, non expiré, appartenant à un compte actif. $lock = verrou pendant la transaction. */
    public function findValid(string $tokenHash, bool $lock = false): ?array
    {
        $sql = 'SELECT t.id, t.utilisateur_id
                FROM password_reset_token t
                JOIN utilisateur u ON u.id = t.utilisateur_id
                WHERE t.token_hash = :h AND t.used_at IS NULL AND t.expires_at > NOW() AND u.actif = 1'
            . ($lock ? ' FOR UPDATE' : '');
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute(['h' => $tokenHash]);

        return $stmt->fetch() ?: null;
    }

    public function invalidateAllForUser(int $userId): void
    {
        Database::pdo()
            ->prepare('UPDATE password_reset_token SET used_at = NOW() WHERE utilisateur_id = :u AND used_at IS NULL')
            ->execute(['u' => $userId]);
    }
}
