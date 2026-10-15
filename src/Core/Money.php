<?php
declare(strict_types=1);

namespace App\Core;

final class Money
{
    /** 1226 -> « 12,26 € » ; 100000 -> « 1 000,00 € » (espaces insécables). */
    public static function format(int $cents): string
    {
        $abs = abs($cents);
        $texte = number_format(intdiv($abs, 100), 0, ',', "\u{202F}") . ',' . str_pad((string) ($abs % 100), 2, '0', STR_PAD_LEFT);

        return ($cents < 0 ? '-' : '') . $texte . "\u{00A0}€";
    }
}
