<?php
declare(strict_types=1);

namespace App\Validation;

/** Nom d'un thème ou d'un régime. */
final class ReferentielValidator
{
    /** @return array{libelle:string,error:?string} */
    public static function validate(mixed $valeur): array
    {
        $libelle = is_string($valeur) ? trim($valeur) : '';
        if (!preg_match('/^[\p{L}\p{N}][\p{L}\p{N} \'\-\.]{1,49}$/u', $libelle)) {
            return ['libelle' => $libelle, 'error' => 'Le nom doit contenir 2 à 50 caractères (lettres, chiffres, espaces, apostrophes et tirets).'];
        }

        return ['libelle' => $libelle, 'error' => null];
    }
}
