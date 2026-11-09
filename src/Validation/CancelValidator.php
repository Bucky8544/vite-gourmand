<?php
declare(strict_types=1);

namespace App\Validation;

/** Annulation par l'équipe : le client doit avoir été contacté (GSM ou mail) et un motif doit être donné. */
final class CancelValidator
{
    public const MODES = ['gsm', 'mail'];
    public const MOTIF_MIN = 5;
    public const MOTIF_MAX = 500;

    /** @return array{data: array{mode_contact:string,motif:string}, errors: array<string,string>, old: array<string,string>} */
    public static function validate(array $in): array
    {
        $errors = [];
        $mode = is_string($in['mode_contact'] ?? null) ? $in['mode_contact'] : '';
        $motif = is_string($in['motif'] ?? null) ? trim($in['motif']) : '';

        if (!in_array($mode, self::MODES, true)) {
            $errors['mode_contact'] = 'Indiquez comment vous avez contacté le client (GSM ou mail).';
        }
        if (mb_strlen($motif) < self::MOTIF_MIN) {
            $errors['motif'] = "Indiquez le motif de l'annulation (" . self::MOTIF_MIN . ' caractères minimum).';
        } elseif (mb_strlen($motif) > self::MOTIF_MAX) {
            $errors['motif'] = 'Le motif ne doit pas dépasser ' . self::MOTIF_MAX . ' caractères.';
        }
        if (($in['contacte'] ?? '') !== '1') {
            $errors['contacte'] = "Vous devez confirmer avoir contacté le client avant d'annuler sa commande.";
        }

        return [
            'data' => ['mode_contact' => $mode, 'motif' => $motif],
            'errors' => $errors,
            'old' => ['mode_contact' => $mode, 'motif' => mb_substr($motif, 0, self::MOTIF_MAX + 1)],
        ];
    }
}
