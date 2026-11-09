<?php
declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;

/** Lecture des menus pour l'équipe : tous les menus, actifs ou non. */
final class MenuAdminRepository
{
    /** @return list<array<string,mixed>> */
    public function listAll(): array
    {
        return Database::pdo()->query(
            "SELECT m.id, m.titre, m.nombre_personne_minimum, m.prix_minimum, m.stock, m.delai_commande_jours, m.actif,
                    t.libelle AS theme, r.libelle AS regime,
                    (SELECT COUNT(*) FROM commande c WHERE c.menu_id = m.id) AS nb_commandes,
                    (SELECT COUNT(*) FROM commande c WHERE c.menu_id = m.id AND c.statut NOT IN ('terminee', 'annulee')) AS nb_en_cours
             FROM menu m
             JOIN theme t ON t.id = m.theme_id
             JOIN regime r ON r.id = m.regime_id
             ORDER BY m.actif DESC, m.id"
        )->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT id, titre, description, nombre_personne_minimum, prix_minimum, conditions, stock,
                    delai_commande_jours, theme_id, regime_id, actif
             FROM menu WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    /** @return list<int> */
    public function platIds(int $menuId): array
    {
        $stmt = Database::pdo()->prepare('SELECT plat_id FROM menu_plat WHERE menu_id = :id ORDER BY plat_id');
        $stmt->execute(['id' => $menuId]);

        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    /** @return list<array{id:int|string,url:string,texte_alternatif:string,position:int|string}> */
    public function images(int $menuId): array
    {
        $stmt = Database::pdo()->prepare('SELECT id, url, texte_alternatif, position FROM menu_image WHERE menu_id = :id ORDER BY position, id');
        $stmt->execute(['id' => $menuId]);

        return $stmt->fetchAll();
    }

    public function countOrders(int $menuId): int
    {
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM commande WHERE menu_id = :id');
        $stmt->execute(['id' => $menuId]);

        return (int) $stmt->fetchColumn();
    }

    /** @return list<array{id:int|string,libelle:string}> */
    public function themes(): array
    {
        return Database::pdo()->query('SELECT id, libelle FROM theme ORDER BY libelle')->fetchAll();
    }

    /** @return list<array{id:int|string,libelle:string}> */
    public function regimes(): array
    {
        return Database::pdo()->query('SELECT id, libelle FROM regime ORDER BY libelle')->fetchAll();
    }
}
