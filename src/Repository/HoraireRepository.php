<?php
declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;

final class HoraireRepository
{
    /** Horaires d'un jour ISO (1 = lundi ... 7 = dimanche). */
    public function forDay(int $jour): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT heure_ouverture, heure_fermeture, ferme FROM horaire WHERE jour = :j');
        $stmt->execute(['j' => $jour]);

        return $stmt->fetch() ?: null;
    }
}
