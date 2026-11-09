<?php
declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use InvalidArgumentException;

/** Thèmes ou régimes : même structure, deux tables. Le nom de table ne vient jamais de la saisie (liste blanche). */
final class ReferentielRepository
{
    public const TABLES = ['theme' => 'theme_id', 'regime' => 'regime_id'];

    private string $colonne;

    public function __construct(private string $table)
    {
        if (!isset(self::TABLES[$table])) {
            throw new InvalidArgumentException('Référentiel inconnu.');
        }
        $this->colonne = self::TABLES[$table];
    }

    /** @return list<array{id:int|string,libelle:string,nb_menus:int|string}> */
    public function all(): array
    {
        return Database::pdo()->query(
            "SELECT t.id, t.libelle, (SELECT COUNT(*) FROM menu m WHERE m.{$this->colonne} = t.id) AS nb_menus
             FROM {$this->table} t ORDER BY t.libelle"
        )->fetchAll();
    }
}
