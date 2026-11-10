<?php
declare(strict_types=1);

namespace App\Validation;

use DateTimeImmutable;

final class AdminStatsFilter
{
    /**
     * Filtres du tableau de bord : un menu et une période (sur la date de la prestation).
     *
     * @return array{menu:?int,du:string,au:string,debut:?DateTimeImmutable,fin:?DateTimeImmutable,errors:array<string,string>}
     */
    public static function parse(array $in): array
    {
        $errors = [];

        $menu = null;
        $texte = is_string($in['menu'] ?? null) ? trim($in['menu']) : '';
        if ($texte !== '') {
            if (preg_match('/^[1-9]\d{0,9}$/', $texte)) {
                $menu = (int) $texte;
            } else {
                $errors['menu'] = "Le menu sélectionné n'est pas valide.";
            }
        }

        $dates = [];
        foreach (['du' => 'de début', 'au' => 'de fin'] as $cle => $libelle) {
            $valeur = is_string($in[$cle] ?? null) ? trim($in[$cle]) : '';
            $date = $valeur === '' ? null : DateTimeImmutable::createFromFormat('!Y-m-d', $valeur);
            if ($valeur !== '' && ($date === false || $date->format('Y-m-d') !== $valeur)) {
                $errors[$cle] = "La date $libelle n'est pas valide.";
                $valeur = '';
                $date = null;
            }
            $dates[$cle] = ['texte' => $valeur, 'date' => $date ?: null];
        }

        if ($dates['du']['date'] !== null && $dates['au']['date'] !== null && $dates['du']['date'] > $dates['au']['date']) {
            $errors['au'] = 'La date de fin doit être après la date de début.';
        }

        $ok = $errors === [];

        return [
            'menu' => $menu,
            'du' => $dates['du']['texte'],
            'au' => $dates['au']['texte'],
            'debut' => $ok ? $dates['du']['date'] : null,
            'fin' => $ok && $dates['au']['date'] !== null ? $dates['au']['date']->setTime(23, 59, 59) : null,
            'errors' => $errors,
        ];
    }
}
