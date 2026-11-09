<?php
declare(strict_types=1);

namespace App\Service;

use App\Repository\HoraireAdminRepository;
use Throwable;

/** Mise en forme des horaires (pied de page). */
final class OpeningHours
{
    private const JOURS = [1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi', 7 => 'Dimanche'];

    /** « 09:00:00 » -> « 9h », « 18:05:00 » -> « 18h05 ». */
    public static function heure(string $t): string
    {
        $heures = (int) substr($t, 0, 2);
        $minutes = (int) substr($t, 3, 2);

        return $heures . 'h' . ($minutes > 0 ? str_pad((string) $minutes, 2, '0', STR_PAD_LEFT) : '');
    }

    /**
     * Regroupe les jours consécutifs qui ont les mêmes horaires.
     *
     * @param list<array{jour:int|string,heure_ouverture:?string,heure_fermeture:?string,ferme:int|string|bool}> $lignes
     * @return list<string>
     */
    public static function lines(array $lignes): array
    {
        usort($lignes, static fn (array $a, array $b): int => (int) $a['jour'] <=> (int) $b['jour']);

        $segments = [];
        foreach ($lignes as $ligne) {
            $jour = (int) $ligne['jour'];
            if (!isset(self::JOURS[$jour])) {
                continue;
            }
            $ferme = (bool) $ligne['ferme'];
            $cle = $ferme ? 'ferme' : $ligne['heure_ouverture'] . '-' . $ligne['heure_fermeture'];

            $dernier = count($segments) - 1;
            if ($dernier >= 0 && $segments[$dernier]['cle'] === $cle && $jour === $segments[$dernier]['fin'] + 1) {
                $segments[$dernier]['fin'] = $jour;
            } else {
                $segments[] = ['cle' => $cle, 'debut' => $jour, 'fin' => $jour, 'ligne' => $ligne, 'ferme' => $ferme];
            }
        }

        return array_map(static function (array $s): string {
            $libelle = $s['debut'] === $s['fin'] ? self::JOURS[$s['debut']] : self::JOURS[$s['debut']] . ' - ' . self::JOURS[$s['fin']];

            return $libelle . ' : ' . ($s['ferme']
                ? 'Fermé'
                : self::heure((string) $s['ligne']['heure_ouverture']) . ' - ' . self::heure((string) $s['ligne']['heure_fermeture']));
        }, $segments);
    }

    /** Lignes du pied de page. Une panne de la base ne doit jamais empêcher d'afficher la page. */
    public static function footerLines(): array
    {
        try {
            return self::lines((new HoraireAdminRepository())->all());
        } catch (Throwable $e) {
            error_log('Horaires indisponibles : ' . $e->getMessage());

            return [];
        }
    }
}
