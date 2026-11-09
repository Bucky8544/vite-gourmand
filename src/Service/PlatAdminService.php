<?php
declare(strict_types=1);

namespace App\Service;

use App\Core\Database;
use Throwable;

final class PlatAdminService
{
    /**
     * @param array{titre:string,type:string,allergene_ids:list<int>} $d données validées
     * @throws CatalogException introuvable
     */
    public function save(?int $id, array $d): int
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();

        try {
            if ($id === null) {
                $pdo->prepare('INSERT INTO plat (titre, type) VALUES (:t, :y)')->execute(['t' => $d['titre'], 'y' => $d['type']]);
                $id = (int) $pdo->lastInsertId();
            } else {
                $stmt = $pdo->prepare('SELECT id FROM plat WHERE id = :id FOR UPDATE');
                $stmt->execute(['id' => $id]);
                if (!$stmt->fetch()) {
                    throw new CatalogException('introuvable', 'Plat introuvable.');
                }
                $pdo->prepare('UPDATE plat SET titre = :t, type = :y WHERE id = :id')->execute(['t' => $d['titre'], 'y' => $d['type'], 'id' => $id]);
            }

            $pdo->prepare('DELETE FROM plat_allergene WHERE plat_id = :id')->execute(['id' => $id]);
            $ajout = $pdo->prepare('INSERT INTO plat_allergene (plat_id, allergene_id) VALUES (:p, :a)');
            foreach ($d['allergene_ids'] as $a) {
                $ajout->execute(['p' => $id, 'a' => $a]);
            }

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        return $id;
    }

    /**
     * Supprime un plat qu'aucun menu n'utilise.
     *
     * @throws CatalogException introuvable ou utilise (le message liste les menus concernés)
     */
    public function delete(int $id): void
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('SELECT id FROM plat WHERE id = :id FOR UPDATE');
            $stmt->execute(['id' => $id]);
            if (!$stmt->fetch()) {
                throw new CatalogException('introuvable', 'Plat introuvable.');
            }

            $stmt = $pdo->prepare('SELECT m.titre FROM menu_plat mp JOIN menu m ON m.id = mp.menu_id WHERE mp.plat_id = :id ORDER BY m.titre');
            $stmt->execute(['id' => $id]);
            $menus = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            if ($menus !== []) {
                throw new CatalogException('utilise', 'Ce plat est utilisé par : ' . implode(', ', $menus) . '. Retirez-le d\'abord de ces menus.');
            }

            $pdo->prepare('DELETE FROM plat WHERE id = :id')->execute(['id' => $id]);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
