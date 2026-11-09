<?php
declare(strict_types=1);

namespace App\Validation;

final class HoraireValidator
{
    private const HEURE = '/^([01]\d|2[0-3]):[0-5]\d$/';

    /**
     * @return array{rows: array<int,array{jour:int,ferme:bool,heure_ouverture:?string,heure_fermeture:?string}>, errors: array<string,string>, old: array<string,string>}
     */
    public static function validate(array $in): array
    {
        $rows = [];
        $errors = [];
        $old = [];

        for ($j = 1; $j <= 7; $j++) {
            $ferme = ($in["ferme_$j"] ?? '') === '1';
            $ouverture = is_string($in["ouverture_$j"] ?? null) ? trim($in["ouverture_$j"]) : '';
            $fermeture = is_string($in["fermeture_$j"] ?? null) ? trim($in["fermeture_$j"]) : '';
            $old["ferme_$j"] = $ferme ? '1' : '';
            $old["ouverture_$j"] = $ouverture;
            $old["fermeture_$j"] = $fermeture;

            if (!$ferme) {
                $okOuverture = (bool) preg_match(self::HEURE, $ouverture);
                $okFermeture = (bool) preg_match(self::HEURE, $fermeture);
                if (!$okOuverture) {
                    $errors["ouverture_$j"] = "Saisissez une heure d'ouverture valide (par exemple 09:00).";
                }
                if (!$okFermeture) {
                    $errors["fermeture_$j"] = 'Saisissez une heure de fermeture valide (par exemple 19:00).';
                } elseif ($okOuverture && $ouverture >= $fermeture) {
                    $errors["fermeture_$j"] = "L'heure de fermeture doit être après l'heure d'ouverture.";
                }
            }

            $rows[$j] = ['jour' => $j, 'ferme' => $ferme, 'heure_ouverture' => $ferme ? null : $ouverture, 'heure_fermeture' => $ferme ? null : $fermeture];
        }

        return ['rows' => $rows, 'errors' => $errors, 'old' => $old];
    }
}
