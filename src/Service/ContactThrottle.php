<?php
declare(strict_types=1);

namespace App\Service;

/** Limite le nombre de messages envoyés depuis une même session : 3 toutes les 15 minutes. */
final class ContactThrottle
{
    public const MAX = 3;
    public const FENETRE = 900;

    /**
     * @param mixed $envois horodatages des envois précédents (valeur de session, donc non fiable)
     * @return array{0:bool,1:list<int>} [autorisé, horodatages à conserver]
     */
    public static function evaluer(mixed $envois, int $maintenant): array
    {
        $recents = [];
        foreach (is_array($envois) ? $envois : [] as $t) {
            if (is_int($t) && $t <= $maintenant && $maintenant - $t < self::FENETRE) {
                $recents[] = $t;
            }
        }

        if (count($recents) >= self::MAX) {
            return [false, $recents];
        }
        $recents[] = $maintenant;

        return [true, $recents];
    }
}
