<?php
declare(strict_types=1);

namespace App\Validation;

use App\Service\Pricing;

final class MenuValidator
{
    public const MAX_IMAGES = 8;

    /**
     * @param array<int,string> $themes id => libellé
     * @param array<int,string> $regimes id => libellé
     * @param list<int> $platsConnus identifiants des plats qui existent
     * @param list<int> $imagesDuMenu identifiants des images déjà enregistrées pour CE menu (lus en base, jamais dans la saisie)
     * @return array{data: array<string,mixed>, errors: array<string,string>, old: array<string,mixed>}
     */
    public static function validate(array $in, array $themes, array $regimes, array $platsConnus, array $imagesDuMenu): array
    {
        $errors = [];
        $get = static fn (string $k): string => is_string($in[$k] ?? null) ? trim($in[$k]) : '';
        $entre = static fn (string $s, int $min, int $max): bool => mb_strlen($s) >= $min && mb_strlen($s) <= $max;

        $titre = $get('titre');
        if (!$entre($titre, 3, 150)) {
            $errors['titre'] = 'Le titre doit contenir entre 3 et 150 caractères.';
        }
        $description = $get('description');
        if (!$entre($description, 10, 2000)) {
            $errors['description'] = 'La description doit contenir entre 10 et 2000 caractères.';
        }
        $conditions = $get('conditions');
        if (!$entre($conditions, 5, 2000)) {
            $errors['conditions'] = 'Les conditions doivent contenir entre 5 et 2000 caractères (par exemple le délai de commande ou les précautions de stockage).';
        }

        $themeId = preg_match('/^[1-9]\d{0,9}$/', $get('theme_id')) ? (int) $get('theme_id') : 0;
        if (!isset($themes[$themeId])) {
            $errors['theme_id'] = 'Choisissez un thème.';
        }
        $regimeId = preg_match('/^[1-9]\d{0,9}$/', $get('regime_id')) ? (int) $get('regime_id') : 0;
        if (!isset($regimes[$regimeId])) {
            $errors['regime_id'] = 'Choisissez un régime.';
        }

        $minimum = 0;
        if (!preg_match('/^[1-9]\d{0,2}$/', $get('nombre_personne_minimum'))) {
            $errors['nombre_personne_minimum'] = 'Le nombre de personnes minimum doit être un entier entre 1 et 999.';
        } else {
            $minimum = (int) $get('nombre_personne_minimum');
        }

        $prix = '0.00';
        if (!preg_match('/^\d{1,5}([.,]\d{1,2})?$/', $get('prix_minimum'))) {
            $errors['prix_minimum'] = 'Saisissez un prix valide (par exemple 150 ou 150,50).';
        } else {
            $centimes = Pricing::toCents(str_replace(',', '.', $get('prix_minimum')));
            if ($centimes <= 0) {
                $errors['prix_minimum'] = 'Le prix doit être supérieur à 0.';
            } else {
                $prix = number_format($centimes / 100, 2, '.', '');
            }
        }

        $stock = 0;
        if (!preg_match('/^\d{1,4}$/', $get('stock'))) {
            $errors['stock'] = 'Le stock doit être un entier entre 0 et 9999.';
        } else {
            $stock = (int) $get('stock');
        }

        $delai = 0;
        if (!preg_match('/^\d{1,3}$/', $get('delai_commande_jours')) || (int) $get('delai_commande_jours') > 365) {
            $errors['delai_commande_jours'] = 'Le délai de commande doit être un nombre de jours entre 0 et 365.';
        } else {
            $delai = (int) $get('delai_commande_jours');
        }

        // Composition : plusieurs plats possibles, un plat peut figurer dans plusieurs menus
        $platIds = [];
        foreach (is_array($in['plats'] ?? null) ? $in['plats'] : [] as $p) {
            if (is_string($p) && preg_match('/^[1-9]\d{0,9}$/', $p)) {
                $platIds[(int) $p] = true;
            }
        }
        $platIds = array_keys($platIds);
        if ($platIds === []) {
            $errors['plats'] = 'Choisissez au moins un plat.';
        } else {
            foreach ($platIds as $p) {
                if (!in_array($p, $platsConnus, true)) {
                    $errors['plats'] = "Un des plats choisis n'existe plus.";
                    break;
                }
            }
        }

        // Images déjà enregistrées : on lit la liste en base, la saisie ne fait que décrire ces images
        $brutes = is_array($in['image'] ?? null) ? $in['image'] : [];
        $images = [];
        $oldImages = [];
        foreach ($imagesDuMenu as $imageId) {
            $b = is_array($brutes[$imageId] ?? null) ? $brutes[$imageId] : [];
            $alt = is_string($b['alt'] ?? null) ? trim($b['alt']) : '';
            $position = is_string($b['position'] ?? null) && preg_match('/^\d{1,2}$/', $b['position']) ? (int) $b['position'] : 0;
            $supprimer = ($b['supprimer'] ?? '') === '1';

            if (!$supprimer && !$entre($alt, 3, 255)) {
                $errors['image_alt_' . $imageId] = 'Décrivez cette image (3 à 255 caractères) : ce texte est lu par les lecteurs d\'écran.';
            }
            $images[] = ['id' => (int) $imageId, 'alt' => $alt, 'position' => $position, 'supprimer' => $supprimer];
            $oldImages[$imageId] = ['alt' => $alt, 'position' => (string) $position, 'supprimer' => $supprimer];
        }

        return [
            'data' => [
                'titre' => $titre,
                'description' => $description,
                'nombre_personne_minimum' => $minimum,
                'prix_minimum' => $prix,
                'conditions' => $conditions,
                'stock' => $stock,
                'delai_commande_jours' => $delai,
                'theme_id' => $themeId,
                'regime_id' => $regimeId,
                'actif' => ($in['actif'] ?? '') === '1',
                'plat_ids' => $platIds,
                'images' => $images,
            ],
            'errors' => $errors,
            'old' => [
                'titre' => $titre, 'description' => $description, 'conditions' => $conditions,
                'theme_id' => $get('theme_id'), 'regime_id' => $get('regime_id'),
                'nombre_personne_minimum' => $get('nombre_personne_minimum'), 'prix_minimum' => $get('prix_minimum'),
                'stock' => $get('stock'), 'delai_commande_jours' => $get('delai_commande_jours'),
                'actif' => ($in['actif'] ?? '') === '1' ? '1' : '', 'plats' => $platIds, 'images' => $oldImages,
            ],
        ];
    }
}
