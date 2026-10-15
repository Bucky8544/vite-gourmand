<?php
declare(strict_types=1);

namespace App\Validation;

final class PasswordPolicy
{
    public const MIN_LENGTH = 10;
    public const MAX_BYTES = 72; // limite de bcrypt

    /** @return list<string> éléments manquants (liste vide si le mot de passe est valide) */
    public static function check(string $password): array
    {
        $manque = [];
        if (mb_strlen($password) < self::MIN_LENGTH) {
            $manque[] = self::MIN_LENGTH . ' caractères minimum';
        }
        if (strlen($password) > self::MAX_BYTES) {
            $manque[] = self::MAX_BYTES . ' octets maximum';
        }
        if (!preg_match('/\p{Lu}/u', $password)) {
            $manque[] = 'une majuscule';
        }
        if (!preg_match('/\p{Ll}/u', $password)) {
            $manque[] = 'une minuscule';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $manque[] = 'un chiffre';
        }
        if (!preg_match('/[^\p{L}\p{N}]/u', $password)) {
            $manque[] = 'un caractère spécial';
        }

        return $manque;
    }
}
