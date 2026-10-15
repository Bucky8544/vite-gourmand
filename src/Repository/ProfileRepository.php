<?php
declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;

final class ProfileRepository
{
    public function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT email, nom, prenom, telephone, adresse_postale, code_postal, ville FROM utilisateur WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }
}
