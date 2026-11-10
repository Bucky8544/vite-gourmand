<?php
declare(strict_types=1);

namespace App\Service;

use App\Core\Database;
use PDOException;

/** Gestion des comptes employés par l'administrateur. Cette classe ne peut créer que des comptes « employé ». */
final class EmployeeAdminService
{
    private const ROLE = 'employe';

    /** @throws CatalogException doublon */
    public function create(string $email, string $motDePasse): int
    {
        $pdo = Database::pdo();

        try {
            $pdo->prepare(
                'INSERT INTO utilisateur (email, mot_de_passe, role_id, actif)
                 VALUES (:e, :h, (SELECT id FROM role WHERE libelle = :r), 1)'
            )->execute(['e' => $email, 'h' => password_hash($motDePasse, PASSWORD_DEFAULT), 'r' => self::ROLE]);
        } catch (PDOException $e) {
            if (($e->errorInfo[1] ?? null) === 1062) {
                throw new CatalogException('doublon', 'Un compte existe déjà avec cette adresse e-mail.');
            }
            throw $e;
        }

        return (int) $pdo->lastInsertId();
    }

    /**
     * Rend un compte employé utilisable ou inutilisable (départ de l'entreprise). Les autres rôles ne sont pas concernés.
     *
     * @throws CatalogException introuvable ou role
     */
    public function setActive(int $id, bool $actif): void
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT r.libelle AS role FROM utilisateur u JOIN role r ON r.id = u.role_id WHERE u.id = :id');
        $stmt->execute(['id' => $id]);
        $compte = $stmt->fetch();

        if (!$compte) {
            throw new CatalogException('introuvable', 'Compte introuvable.');
        }
        if ($compte['role'] !== self::ROLE) {
            throw new CatalogException('role', 'Seuls les comptes employés peuvent être activés ou désactivés ici.');
        }

        $pdo->prepare('UPDATE utilisateur SET actif = :a WHERE id = :id')->execute(['a' => $actif ? 1 : 0, 'id' => $id]);
    }
}
