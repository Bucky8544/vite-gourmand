<?php
declare(strict_types=1);

namespace App\Validation;

final class ReviewValidator
{
    public const MAX_COMMENTAIRE = 1000;

    /** @return array{data: array{note:int,commentaire:string}, errors: array<string,string>, old: array<string,string>} */
    public static function validate(array $in): array
    {
        $errors = [];
        $note = is_string($in['note'] ?? null) ? trim($in['note']) : '';
        $commentaire = is_string($in['commentaire'] ?? null) ? trim($in['commentaire']) : '';

        if (!preg_match('/^[1-5]$/', $note)) {
            $errors['note'] = 'Choisissez une note de 1 à 5.';
        }
        if ($commentaire === '') {
            $errors['commentaire'] = 'Le commentaire est obligatoire.';
        } elseif (mb_strlen($commentaire) > self::MAX_COMMENTAIRE) {
            $errors['commentaire'] = 'Le commentaire ne doit pas dépasser ' . self::MAX_COMMENTAIRE . ' caractères.';
        }

        return [
            'data' => ['note' => (int) $note, 'commentaire' => $commentaire],
            'errors' => $errors,
            'old' => ['note' => $note, 'commentaire' => mb_substr($commentaire, 0, self::MAX_COMMENTAIRE + 1)],
        ];
    }
}
