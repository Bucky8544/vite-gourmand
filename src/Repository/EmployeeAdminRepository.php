<?php
declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;

final class EmployeeAdminRepository
{
    /** Comptes employés uniquement : jamais les clients ni l'administrateur. @return list<array{id:int|string,email:string,actif:int|string,created_at:string}> */
    public function all(): array
    {
        return Database::pdo()->query(
            "SELECT u.id, u.email, u.actif, u.created_at
             FROM utilisateur u
             JOIN role r ON r.id = u.role_id
             WHERE r.libelle = 'employe'
             ORDER BY u.actif DESC, u.email"
        )->fetchAll();
    }
}
