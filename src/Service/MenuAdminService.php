<?php
declare(strict_types=1);

namespace App\Service;

use App\Core\Database;
use Throwable;

/** Création, modification, activation et suppression des menus. Chaque opération est une transaction. */
final class MenuAdminService
{
    private ImageStorage $images;

    public function __construct(?ImageStorage $images = null)
    {
        $this->images = $images ?? new ImageStorage();
    }

    /**
     * Crée ou modifie un menu avec sa composition et ses images.
     *
     * @param array<string,mixed> $d données validées (MenuValidator)
     * @param list<array{id:int,alt:string,position:int,supprimer:bool}> $existantes images déjà enregistrées
     * @param list<array{tmp:string,ext:string,alt:string}> $nouvelles images à ajouter (déjà inspectées)
     * @return int identifiant du menu
     * @throws CatalogException introuvable
     */
    public function save(?int $id, array $d, array $existantes, array $nouvelles): int
    {
        $pdo = Database::pdo();

        // Les fichiers sont écrits avant la transaction ; ils sont supprimés si elle échoue
        $ajoutees = [];
        try {
            foreach ($nouvelles as $n) {
                $ajoutees[] = ['url' => $this->images->enregistrer($n['tmp'], $n['ext']), 'alt' => $n['alt']];
            }
        } catch (Throwable $e) {
            foreach ($ajoutees as $a) {
                $this->images->supprimer($a['url']);
            }
            throw $e;
        }

        $params = [
            'titre' => $d['titre'], 'description' => $d['description'], 'min' => $d['nombre_personne_minimum'],
            'prix' => $d['prix_minimum'], 'conditions' => $d['conditions'], 'stock' => $d['stock'],
            'delai' => $d['delai_commande_jours'], 'theme' => $d['theme_id'], 'regime' => $d['regime_id'], 'actif' => $d['actif'] ? 1 : 0,
        ];
        $aSupprimer = [];

        $pdo->beginTransaction();
        try {
            if ($id === null) {
                $pdo->prepare(
                    'INSERT INTO menu (titre, description, nombre_personne_minimum, prix_minimum, conditions, stock, delai_commande_jours, theme_id, regime_id, actif)
                     VALUES (:titre, :description, :min, :prix, :conditions, :stock, :delai, :theme, :regime, :actif)'
                )->execute($params);
                $id = (int) $pdo->lastInsertId();
            } else {
                // Même verrou que la prise de commande : pas de modification pendant qu'une commande se valide
                $stmt = $pdo->prepare('SELECT id FROM menu WHERE id = :id FOR UPDATE');
                $stmt->execute(['id' => $id]);
                if (!$stmt->fetch()) {
                    throw new CatalogException('introuvable', 'Menu introuvable.');
                }
                $pdo->prepare(
                    'UPDATE menu SET titre = :titre, description = :description, nombre_personne_minimum = :min, prix_minimum = :prix,
                            conditions = :conditions, stock = :stock, delai_commande_jours = :delai, theme_id = :theme,
                            regime_id = :regime, actif = :actif
                     WHERE id = :id'
                )->execute($params + ['id' => $id]);
            }

            // Composition
            $pdo->prepare('DELETE FROM menu_plat WHERE menu_id = :id')->execute(['id' => $id]);
            $ajout = $pdo->prepare('INSERT INTO menu_plat (menu_id, plat_id) VALUES (:m, :p)');
            foreach ($d['plat_ids'] as $platId) {
                $ajout->execute(['m' => $id, 'p' => $platId]);
            }

            // Images déjà enregistrées : texte alternatif, position, suppression
            $stmt = $pdo->prepare('SELECT id, url FROM menu_image WHERE menu_id = :id');
            $stmt->execute(['id' => $id]);
            $enBase = array_column($stmt->fetchAll(), 'url', 'id');
            foreach ($existantes as $e) {
                if (!isset($enBase[$e['id']])) {
                    continue; // image d'un autre menu : ignorée
                }
                if ($e['supprimer']) {
                    $pdo->prepare('DELETE FROM menu_image WHERE id = :i AND menu_id = :m')->execute(['i' => $e['id'], 'm' => $id]);
                    $aSupprimer[] = $enBase[$e['id']];
                } else {
                    $pdo->prepare('UPDATE menu_image SET texte_alternatif = :a, position = :p WHERE id = :i AND menu_id = :m')
                        ->execute(['a' => $e['alt'], 'p' => $e['position'], 'i' => $e['id'], 'm' => $id]);
                }
            }

            // Nouvelles images : à la suite, puis positions renumérotées de 0 à n-1
            $position = 100; // la colonne est un TINYINT (0 à 255) : les positions saisies vont de 0 à 99
            foreach ($ajoutees as $a) {
                $pdo->prepare('INSERT INTO menu_image (menu_id, url, texte_alternatif, position) VALUES (:m, :u, :a, :p)')
                    ->execute(['m' => $id, 'u' => $a['url'], 'a' => $a['alt'], 'p' => $position++]);
            }
            $stmt = $pdo->prepare('SELECT id FROM menu_image WHERE menu_id = :id ORDER BY position, id');
            $stmt->execute(['id' => $id]);
            foreach ($stmt->fetchAll(\PDO::FETCH_COLUMN) as $i => $imageId) {
                $pdo->prepare('UPDATE menu_image SET position = :p WHERE id = :i')->execute(['p' => $i, 'i' => $imageId]);
            }

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            foreach ($ajoutees as $a) {
                $this->images->supprimer($a['url']);
            }
            throw $e;
        }

        // Après la validation : les fichiers retirés ne servent plus à personne
        foreach ($aSupprimer as $url) {
            $this->images->supprimer($url);
        }

        return $id;
    }

    /** @throws CatalogException introuvable */
    public function setActive(int $id, bool $actif): void
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT id FROM menu WHERE id = :id');
        $stmt->execute(['id' => $id]);
        if (!$stmt->fetch()) {
            throw new CatalogException('introuvable', 'Menu introuvable.');
        }
        $pdo->prepare('UPDATE menu SET actif = :a WHERE id = :id')->execute(['a' => $actif ? 1 : 0, 'id' => $id]);
    }

    /**
     * Supprime un menu qui n'a jamais été commandé (la composition et les images partent avec lui).
     *
     * @throws CatalogException introuvable ou utilise
     */
    public function delete(int $id): void
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('SELECT id FROM menu WHERE id = :id FOR UPDATE');
            $stmt->execute(['id' => $id]);
            if (!$stmt->fetch()) {
                throw new CatalogException('introuvable', 'Menu introuvable.');
            }

            $stmt = $pdo->prepare('SELECT COUNT(*) FROM commande WHERE menu_id = :id');
            $stmt->execute(['id' => $id]);
            if ((int) $stmt->fetchColumn() > 0) {
                throw new CatalogException('utilise', 'Ce menu a déjà été commandé : il ne peut pas être supprimé sans effacer l\'historique des commandes. Désactivez-le à la place.');
            }

            $stmt = $pdo->prepare('SELECT url FROM menu_image WHERE menu_id = :id');
            $stmt->execute(['id' => $id]);
            $urls = $stmt->fetchAll(\PDO::FETCH_COLUMN);

            $pdo->prepare('DELETE FROM menu WHERE id = :id')->execute(['id' => $id]);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        foreach ($urls as $url) {
            $this->images->supprimer((string) $url);
        }
    }
}
