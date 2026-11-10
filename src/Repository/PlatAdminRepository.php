<?php
declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;

final class PlatAdminRepository
{
    /**
     * Tous les plats, avec leurs allergènes et les menus qui les utilisent.
     *
     * @return list<array{id:int,titre:string,type:string,allergene_ids:list<int>,allergenes:list<string>,menus:list<array{id:int,titre:string}>}>
     */
    public function listAll(): array
    {
        $pdo = Database::pdo();
        $plats = [];
        foreach ($pdo->query("SELECT id, titre, type FROM plat ORDER BY FIELD(type, 'entree', 'plat', 'dessert'), titre")->fetchAll() as $l) {
            $plats[(int) $l['id']] = ['id' => (int) $l['id'], 'titre' => $l['titre'], 'type' => $l['type'],
                'allergene_ids' => [], 'allergenes' => [], 'menus' => []];
        }
        foreach ($pdo->query('SELECT pa.plat_id, a.id, a.libelle FROM plat_allergene pa JOIN allergene a ON a.id = pa.allergene_id ORDER BY a.id')->fetchAll() as $l) {
            if (isset($plats[(int) $l['plat_id']])) {
                $plats[(int) $l['plat_id']]['allergene_ids'][] = (int) $l['id'];
                $plats[(int) $l['plat_id']]['allergenes'][] = $l['libelle'];
            }
        }
        foreach ($pdo->query('SELECT mp.plat_id, m.id, m.titre FROM menu_plat mp JOIN menu m ON m.id = mp.menu_id ORDER BY m.id')->fetchAll() as $l) {
            if (isset($plats[(int) $l['plat_id']])) {
                $plats[(int) $l['plat_id']]['menus'][] = ['id' => (int) $l['id'], 'titre' => $l['titre']];
            }
        }

        return array_values($plats);
    }

    public function find(int $id): ?array
    {
        foreach ($this->listAll() as $plat) {
            if ($plat['id'] === $id) {
                return $plat;
            }
        }

        return null;
    }

    /** @return list<array{id:int|string,libelle:string}> */
    public function allergenes(): array
    {
        return Database::pdo()->query('SELECT id, libelle FROM allergene ORDER BY id')->fetchAll();
    }
}
