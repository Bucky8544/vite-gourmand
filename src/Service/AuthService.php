<?php
declare(strict_types=1);

namespace App\Service;

use App\Repository\UserRepository;

final class AuthService
{
    private static ?string $fallbackHash = null;

    public function __construct(private ?UserRepository $users = null)
    {
        $this->users ??= new UserRepository();
    }

    /**
     * Vérifie les identifiants. Renvoie l'utilisateur (sans le hash) ou null, sans jamais dire pourquoi.
     */
    public function attempt(string $email, string $password): ?array
    {
        $user = $this->users->findByEmail(mb_strtolower(trim($email)));

        // Toujours vérifier un hash, même si l'e-mail est inconnu : le temps de réponse reste identique
        $valid = password_verify($password, $user['mot_de_passe'] ?? self::dummyHash());

        if ($user === null || !$valid || (int) $user['actif'] !== 1) {
            return null;
        }

        // Si le coût de hachage a évolué, on met le hash à jour
        if (password_needs_rehash($user['mot_de_passe'], PASSWORD_DEFAULT)) {
            $this->users->updatePasswordHash((int) $user['id'], password_hash($password, PASSWORD_DEFAULT));
        }

        unset($user['mot_de_passe']);

        return $user;
    }

    private static function dummyHash(): string
    {
        $file = dirname(__DIR__, 2) . '/config/dummy_hash.php';
        if (is_file($file)) {
            $hash = require $file;
            if (is_string($hash)) {
                return $hash;
            }
        }

        return self::$fallbackHash ??= password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
    }
}
