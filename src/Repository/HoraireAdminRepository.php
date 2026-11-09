<?php
declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use Throwable;

final class HoraireAdminRepository
{
    /** @return list<array{jour:int|string,heure_ouverture:?string,heure_fermeture:?string,ferme:int|string}> */
    public function all(): array
    {
        return Database::pdo()->query('SELECT jour, heure_ouverture, heure_fermeture, ferme FROM horaire ORDER BY jour')->fetchAll();
    }

    /** @param array<int,array{jour:int,ferme:bool,heure_ouverture:?string,heure_fermeture:?string}> $rows */
    public function saveAll(array $rows): void
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO horaire (jour, heure_ouverture, heure_fermeture, ferme) VALUES (:j, :o, :f, :x)
                 ON DUPLICATE KEY UPDATE heure_ouverture = VALUES(heure_ouverture), heure_fermeture = VALUES(heure_fermeture), ferme = VALUES(ferme)'
            );
            foreach ($rows as $r) {
                $stmt->execute([
                    'j' => $r['jour'],
                    'o' => $r['ferme'] ? null : $r['heure_ouverture'] . ':00',
                    'f' => $r['ferme'] ? null : $r['heure_fermeture'] . ':00',
                    'x' => $r['ferme'] ? 1 : 0,
                ]);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
