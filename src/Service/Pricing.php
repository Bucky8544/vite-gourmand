<?php
declare(strict_types=1);

namespace App\Service;

/** Règles de prix de Julie. Tous les montants sont en centimes (entiers) : aucune erreur d'arrondi. */
final class Pricing
{
    public const REMISE_POURCENT = 10;
    public const REMISE_PERSONNES_EN_PLUS = 5; // remise à partir de « minimum + 5 » personnes
    public const LIVRAISON_FORFAIT = 500;      // 5 € hors Bordeaux
    public const LIVRAISON_PAR_KM = 59;        // + 0,59 € par kilomètre

    public static function toCents(float|int|string $euros): int
    {
        return (int) round((float) $euros * 100);
    }

    /** Prix du menu pour $personnes avant remise, arrondi au centime le plus proche. */
    public static function prixMenuBrut(int $prixMinimum, int $minimum, int $personnes): int
    {
        return intdiv(2 * $prixMinimum * $personnes + $minimum, 2 * $minimum);
    }

    public static function seuilRemise(int $minimum): int
    {
        return $minimum + self::REMISE_PERSONNES_EN_PLUS;
    }

    public static function remise(int $brut, int $minimum, int $personnes): int
    {
        if ($personnes < self::seuilRemise($minimum)) {
            return 0;
        }

        return intdiv($brut * self::REMISE_POURCENT + 50, 100);
    }

    /** Frais de livraison. $kmDixiemes : distance en dixièmes de kilomètre. */
    public static function livraison(bool $dansBordeaux, int $kmDixiemes): int
    {
        if ($dansBordeaux) {
            return 0;
        }

        return self::LIVRAISON_FORFAIT + intdiv(self::LIVRAISON_PAR_KM * $kmDixiemes + 5, 10);
    }

    /** @return array{brut:int,remise:int,menu:int,livraison:int,total:int} */
    public static function devis(int $prixMinimum, int $minimum, int $personnes, bool $dansBordeaux, int $kmDixiemes): array
    {
        $brut = self::prixMenuBrut($prixMinimum, $minimum, $personnes);
        $remise = self::remise($brut, $minimum, $personnes);
        $menu = $brut - $remise;
        $livraison = self::livraison($dansBordeaux, $kmDixiemes);

        return ['brut' => $brut, 'remise' => $remise, 'menu' => $menu, 'livraison' => $livraison, 'total' => $menu + $livraison];
    }
}
