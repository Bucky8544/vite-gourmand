<?php
declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;

final class MenuRepository
{
    /**
     * Menus actifs, éventuellement filtrés. Clés de $f : prix_max, prix_de, prix_a, theme_id, regime_id, personnes.
     *
     * @return list<array<string,mixed>>
     */
    public function search(array $f = []): array
    {
        $where = ['m.actif = 1'];
        $p = [];

        if (($f['prix_max'] ?? null) !== null) {
            $where[] = 'm.prix_minimum <= :prix_max';
            $p['prix_max'] = $f['prix_max'];
        }
        if (($f['prix_de'] ?? null) !== null) {
            $where[] = 'm.prix_minimum >= :prix_de';
            $p['prix_de'] = $f['prix_de'];
        }
        if (($f['prix_a'] ?? null) !== null) {
            $where[] = 'm.prix_minimum <= :prix_a';
            $p['prix_a'] = $f['prix_a'];
        }
        if (($f['theme_id'] ?? null) !== null) {
            $where[] = 'm.theme_id = :theme';
            $p['theme'] = $f['theme_id'];
        }
        if (($f['regime_id'] ?? null) !== null) {
            $where[] = 'm.regime_id = :regime';
            $p['regime'] = $f['regime_id'];
        }
        if (($f['personnes'] ?? null) !== null) {
            $where[] = 'm.nombre_personne_minimum <= :personnes';
            $p['personnes'] = $f['personnes'];
        }

        $sql = 'SELECT m.id, m.titre, m.description, m.nombre_personne_minimum, m.prix_minimum, m.stock, m.delai_commande_jours,
                       t.libelle AS theme, r.libelle AS regime,
                       (SELECT mi.url FROM menu_image mi WHERE mi.menu_id = m.id ORDER BY mi.position, mi.id LIMIT 1) AS image_url,
                       (SELECT mi.texte_alternatif FROM menu_image mi WHERE mi.menu_id = m.id ORDER BY mi.position, mi.id LIMIT 1) AS image_alt
                FROM menu m
                JOIN theme t ON t.id = m.theme_id
                JOIN regime r ON r.id = m.regime_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY m.id';

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($p);

        return $stmt->fetchAll();
    }

    /** Menu actif avec toutes ses informations, ou null (inconnu ou désactivé). */
    public function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT m.id, m.titre, m.description, m.nombre_personne_minimum, m.prix_minimum, m.conditions, m.stock, m.delai_commande_jours,
                    t.libelle AS theme, r.libelle AS regime
             FROM menu m
             JOIN theme t ON t.id = m.theme_id
             JOIN regime r ON r.id = m.regime_id
             WHERE m.id = :id AND m.actif = 1'
        );
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    /** @return list<array{url:string,texte_alternatif:string,position:int|string}> */
    public function images(int $menuId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT url, texte_alternatif, position FROM menu_image WHERE menu_id = :id ORDER BY position, id'
        );
        $stmt->execute(['id' => $menuId]);

        return $stmt->fetchAll();
    }

    /**
     * Plats du menu regroupés par type, chacun avec la liste de ses allergènes.
     *
     * @return array{entree: list<array>, plat: list<array>, dessert: list<array>}
     */
    public function composition(int $menuId): array
    {
        $stmt = Database::pdo()->prepare(
            "SELECT p.id, p.titre, p.type, a.libelle AS allergene
             FROM menu_plat mp
             JOIN plat p ON p.id = mp.plat_id
             LEFT JOIN plat_allergene pa ON pa.plat_id = p.id
             LEFT JOIN allergene a ON a.id = pa.allergene_id
             WHERE mp.menu_id = :id
             ORDER BY FIELD(p.type, 'entree', 'plat', 'dessert'), p.id, a.id"
        );
        $stmt->execute(['id' => $menuId]);

        $plats = [];
        foreach ($stmt->fetchAll() as $ligne) {
            $pid = (int) $ligne['id'];
            if (!isset($plats[$pid])) {
                $plats[$pid] = ['id' => $pid, 'titre' => $ligne['titre'], 'type' => $ligne['type'], 'allergenes' => []];
            }
            if ($ligne['allergene'] !== null) {
                $plats[$pid]['allergenes'][] = $ligne['allergene'];
            }
        }

        $groupes = ['entree' => [], 'plat' => [], 'dessert' => []];
        foreach ($plats as $plat) {
            $groupes[$plat['type']][] = $plat;
        }

        return $groupes;
    }

    /** @return list<array{id:int|string,libelle:string}> */
    public function themes(): array
    {
        return Database::pdo()->query('SELECT id, libelle FROM theme ORDER BY id')->fetchAll();
    }

    /** @return list<array{id:int|string,libelle:string}> */
    public function regimes(): array
    {
        return Database::pdo()->query('SELECT id, libelle FROM regime ORDER BY id')->fetchAll();
    }
}
