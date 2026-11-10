<?php
declare(strict_types=1);

namespace App\Validation;

final class MenuFilter
{
    public const FIELDS = ['prix_max', 'prix_de', 'prix_a', 'theme', 'regime', 'personnes'];

    /**
     * @param array<string,mixed> $in paramètres bruts (query string)
     * @return array{filters: array<string,float|int|null>, errors: array<string,string>}
     */
    public static function parse(array $in): array
    {
        $errors = [];

        $prixMax = self::price($in, 'prix_max', 'Le prix maximum doit être un montant positif (par exemple 85 ou 85,50).', $errors);
        $prixDe = self::price($in, 'prix_de', 'Le prix minimum de la fourchette doit être un montant positif (par exemple 85 ou 85,50).', $errors);
        $prixA = self::price($in, 'prix_a', 'Le prix maximum de la fourchette doit être un montant positif (par exemple 85 ou 85,50).', $errors);
        $theme = self::integer($in, 'theme', "Le thème sélectionné n'est pas valide.", $errors);
        $regime = self::integer($in, 'regime', "Le régime sélectionné n'est pas valide.", $errors);
        $personnes = self::integer($in, 'personnes', 'Le nombre de personnes doit être un entier supérieur ou égal à 1.', $errors);

        if ($prixDe !== null && $prixA !== null && $prixDe > $prixA) {
            $errors['prix_a'] = 'Le prix maximum de la fourchette doit être supérieur ou égal au prix minimum.';
            $prixDe = $prixA = null;
        }

        return [
            'filters' => [
                'prix_max' => $prixMax,
                'prix_de' => $prixDe,
                'prix_a' => $prixA,
                'theme_id' => $theme,
                'regime_id' => $regime,
                'personnes' => $personnes,
            ],
            'errors' => $errors,
        ];
    }

    private static function raw(array $in, string $key, string $message, array &$errors): ?string
    {
        if (!array_key_exists($key, $in)) {
            return null;
        }
        if (!is_string($in[$key])) { // par exemple ?theme[]=1
            $errors[$key] = $message;

            return null;
        }
        $v = trim($in[$key]);

        return $v === '' ? null : $v;
    }

    private static function price(array $in, string $key, string $message, array &$errors): ?float
    {
        $v = self::raw($in, $key, $message, $errors);
        if ($v === null) {
            return null;
        }
        if (!preg_match('/^\d{1,6}([.,]\d{1,2})?$/', $v)) {
            $errors[$key] = $message;

            return null;
        }

        return (float) str_replace(',', '.', $v);
    }

    private static function integer(array $in, string $key, string $message, array &$errors): ?int
    {
        $v = self::raw($in, $key, $message, $errors);
        if ($v === null) {
            return null;
        }
        if (!preg_match('/^[1-9]\d{0,5}$/', $v)) {
            $errors[$key] = $message;

            return null;
        }

        return (int) $v;
    }
}
