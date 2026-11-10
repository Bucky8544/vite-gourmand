<?php
declare(strict_types=1);

namespace App\Core;

/** Informations de l'entreprise, lues dans config/entreprise.php. */
final class Entreprise
{
    private static ?array $donnees = null;

    /** @return array<string,mixed> */
    public static function all(): array
    {
        return self::$donnees ??= require dirname(__DIR__, 2) . '/config/entreprise.php';
    }

    public static function get(string $cle): string
    {
        return (string) (self::all()[$cle] ?? '');
    }

    /** Vrai tant que les informations sont des exemples : les pages légales affichent alors un avertissement. */
    public static function fictif(): bool
    {
        return (bool) (self::all()['fictif'] ?? false);
    }
}
