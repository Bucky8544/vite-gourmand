<?php
declare(strict_types=1);

namespace App\Service;

/**
 * Graphique en barres horizontales généré en SVG côté serveur : aucune bibliothèque, aucun script, aucun style en ligne
 * (compatible avec la politique de sécurité). Les couleurs viennent de classes CSS ; le contenu est lisible par les
 * lecteurs d'écran (titre et description) et doit toujours être doublé d'un tableau.
 */
final class BarChart
{
    private const LARGEUR = 640;
    private const BARRE_MAX = 440;
    private const LIGNE = 56;

    /**
     * @param list<array{label:string,valeur:int|float,texte:string}> $barres
     * @param string $id identifiant unique du graphique dans la page (lettres, chiffres, tirets)
     */
    public static function svg(array $barres, string $titre, string $description, string $id): string
    {
        if ($barres === []) {
            return '';
        }

        $id = preg_replace('/[^A-Za-z0-9_-]/', '', $id) ?: 'graph';
        $max = max(array_map(static fn (array $b): float => (float) $b['valeur'], $barres));
        $hauteur = count($barres) * self::LIGNE + 8;
        $e = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $svg = '<svg class="graph" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . self::LARGEUR . ' ' . $hauteur . '" role="img" '
            . 'aria-labelledby="graph-' . $id . '-titre graph-' . $id . '-desc">'
            . '<title id="graph-' . $id . '-titre">' . $e($titre) . '</title>'
            . '<desc id="graph-' . $id . '-desc">' . $e($description) . '</desc>';

        foreach ($barres as $i => $b) {
            $haut = $i * self::LIGNE + 4;
            $valeur = (float) $b['valeur'];
            $largeur = ($max > 0 && $valeur > 0) ? max(2.0, round($valeur / $max * self::BARRE_MAX, 1)) : 0.0;
            $label = mb_strimwidth($b['label'], 0, 70, '…');

            $svg .= '<g><title>' . $e($b['label'] . ' : ' . $b['texte']) . '</title>'
                . '<text class="graph-label" x="0" y="' . self::n($haut + 17) . '">' . $e($label) . '</text>'
                . '<rect class="graph-fond" x="0" y="' . self::n($haut + 26) . '" width="' . self::BARRE_MAX . '" height="18" rx="4"/>'
                . ($largeur > 0
                    ? '<rect class="graph-barre" x="0" y="' . self::n($haut + 26) . '" width="' . self::n($largeur) . '" height="18" rx="4"/>'
                    : '')
                . '<text class="graph-valeur" x="452" y="' . self::n($haut + 40) . '">' . $e($b['texte']) . '</text></g>';
        }

        return $svg . '</svg>';
    }

    /** 30.0 -> « 30 », 219.5 -> « 219.5 » (le point décimal est toujours un point, quelle que soit la langue). */
    private static function n(float $v): string
    {
        return rtrim(rtrim(number_format($v, 1, '.', ''), '0'), '.');
    }
}
