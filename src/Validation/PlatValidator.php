<?php
declare(strict_types=1);

namespace App\Validation;

final class PlatValidator
{
    public const TYPES = ['entree' => 'Entrée', 'plat' => 'Plat', 'dessert' => 'Dessert'];

    /**
     * @param list<int> $allergenesConnus
     * @return array{data: array{titre:string,type:string,allergene_ids:list<int>}, errors: array<string,string>}
     */
    public static function validate(array $in, array $allergenesConnus): array
    {
        $errors = [];
        $titre = is_string($in['titre'] ?? null) ? trim($in['titre']) : '';
        $type = is_string($in['type'] ?? null) ? $in['type'] : '';

        if (mb_strlen($titre) < 2 || mb_strlen($titre) > 150) {
            $errors['titre'] = 'Le nom du plat doit contenir entre 2 et 150 caractères.';
        }
        if (!isset(self::TYPES[$type])) {
            $errors['type'] = 'Choisissez le type de plat (entrée, plat ou dessert).';
        }

        $ids = [];
        foreach (is_array($in['allergenes'] ?? null) ? $in['allergenes'] : [] as $a) {
            if (is_string($a) && preg_match('/^[1-9]\d{0,9}$/', $a)) {
                $ids[(int) $a] = true;
            }
        }
        $ids = array_keys($ids);
        foreach ($ids as $a) {
            if (!in_array($a, $allergenesConnus, true)) {
                $errors['allergenes'] = "Un des allergènes choisis n'existe pas.";
                break;
            }
        }

        return ['data' => ['titre' => $titre, 'type' => $type, 'allergene_ids' => $ids], 'errors' => $errors];
    }
}
