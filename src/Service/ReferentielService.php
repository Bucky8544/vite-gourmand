<?php
declare(strict_types=1);

namespace App\Service;

use App\Core\Database;
use App\Repository\ReferentielRepository;
use InvalidArgumentException;
use PDOException;

/** Ajout, renommage et suppression des thèmes ou des régimes. */
final class ReferentielService
{
    private string $colonne;

    public function __construct(private string $table)
    {
        if (!isset(ReferentielRepository::TABLES[$table])) {
            throw new InvalidArgumentException('Référentiel inconnu.');
        }
        $this->colonne = ReferentielRepository::TABLES[$table];
    }

    /** @throws CatalogException doublon */
    public function create(string $libelle): int
    {
        $pdo = Database::pdo();
        try {
            $pdo->prepare("INSERT INTO {$this->table} (libelle) VALUES (:l)")->execute(['l' => $libelle]);
        } catch (PDOException $e) {
            $this->doublon($e);
        }

        return (int) $pdo->lastInsertId();
    }

    /** @throws CatalogException introuvable ou doublon */
    public function rename(int $id, string $libelle): void
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("SELECT id FROM {$this->table} WHERE id = :id");
        $stmt->execute(['id' => $id]);
        if (!$stmt->fetch()) {
            throw new CatalogException('introuvable', 'Élément introuvable.');
        }
        try {
            $pdo->prepare("UPDATE {$this->table} SET libelle = :l WHERE id = :id")->execute(['l' => $libelle, 'id' => $id]);
        } catch (PDOException $e) {
            $this->doublon($e);
        }
    }

    /** @throws CatalogException introuvable ou utilise */
    public function delete(int $id): void
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("SELECT id FROM {$this->table} WHERE id = :id");
        $stmt->execute(['id' => $id]);
        if (!$stmt->fetch()) {
            throw new CatalogException('introuvable', 'Élément introuvable.');
        }

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM menu WHERE {$this->colonne} = :id");
        $stmt->execute(['id' => $id]);
        if ((int) $stmt->fetchColumn() > 0) {
            throw new CatalogException('utilise', 'Cet élément est utilisé par des menus : changez d\'abord le thème ou le régime de ces menus.');
        }

        $pdo->prepare("DELETE FROM {$this->table} WHERE id = :id")->execute(['id' => $id]);
    }

    private function doublon(PDOException $e): never
    {
        if (($e->errorInfo[1] ?? null) === 1062) {
            throw new CatalogException('doublon', 'Ce nom existe déjà.');
        }
        throw $e;
    }
}
