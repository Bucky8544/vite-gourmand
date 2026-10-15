<?php
declare(strict_types=1);

namespace App\Service;

use App\Core\Database;
use App\Repository\PasswordResetRepository;
use App\Repository\UserRepository;
use Closure;
use Throwable;

final class PasswordResetService
{
    public const TTL_MINUTES = 60;
    public const MAX_PAR_HEURE = 3;

    private UserRepository $users;
    private PasswordResetRepository $tokens;
    private Closure $sendLink;
    private Closure $sendChanged;

    /** Les deux fermetures d'envoi de mail sont injectables pour les tests. */
    public function __construct(
        ?UserRepository $users = null,
        ?PasswordResetRepository $tokens = null,
        ?Closure $sendLink = null,
        ?Closure $sendChanged = null
    ) {
        $this->users = $users ?? new UserRepository();
        $this->tokens = $tokens ?? new PasswordResetRepository();
        $this->sendLink = $sendLink ?? static fn (string $email, ?string $prenom, string $url) => (new Mailer())->sendPasswordReset($email, $prenom, $url);
        $this->sendChanged = $sendChanged ?? static fn (string $email, ?string $prenom) => (new Mailer())->sendPasswordChanged($email, $prenom);
    }

    /** Ne dit jamais si le compte existe : le résultat est identique dans tous les cas. */
    public function requestReset(string $email): void
    {
        $user = $this->users->findByEmail(mb_strtolower(trim($email)));
        if ($user === null || (int) $user['actif'] !== 1) {
            return;
        }
        if ($this->tokens->countRecent((int) $user['id']) >= self::MAX_PAR_HEURE) {
            return;
        }

        $token = bin2hex(random_bytes(32));
        $this->tokens->create((int) $user['id'], hash('sha256', $token), self::TTL_MINUTES);

        // Le lien vient de APP_URL, jamais de l'en-tête Host fourni par le client
        $url = rtrim(getenv('APP_URL') ?: 'http://localhost:8081', '/') . '/reinitialisation/' . $token;

        try {
            ($this->sendLink)($user['email'], $user['prenom'], $url);
        } catch (Throwable $e) {
            error_log('Mail de réinitialisation non envoyé : ' . $e->getMessage());
        }
    }

    public function isValid(string $token): bool
    {
        return self::wellFormed($token) && $this->tokens->findValid(hash('sha256', $token)) !== null;
    }

    /** Change le mot de passe si le jeton est valide. La politique du mot de passe est vérifiée par l'appelant. */
    public function reset(string $token, string $newPassword): bool
    {
        if (!self::wellFormed($token)) {
            return false;
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $row = $this->tokens->findValid(hash('sha256', $token), true); // verrou : pas de double utilisation
            if ($row === null) {
                $pdo->rollBack();

                return false;
            }
            $userId = (int) $row['utilisateur_id'];
            $this->users->updatePasswordHash($userId, password_hash($newPassword, PASSWORD_DEFAULT));
            $this->tokens->invalidateAllForUser($userId);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        $user = $this->users->findActiveById($userId);
        if ($user !== null) {
            try {
                ($this->sendChanged)($user['email'], $user['prenom']);
            } catch (Throwable $e) {
                error_log('Mail de confirmation non envoyé : ' . $e->getMessage());
            }
        }

        return true;
    }

    private static function wellFormed(string $token): bool
    {
        return (bool) preg_match('/^[0-9a-f]{64}$/', $token);
    }
}
