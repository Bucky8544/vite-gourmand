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

    /** L'e-mail (identifiant) et le rôle ne sont jamais modifiés ici. */
    public function update(int $id, array $d): void
    {
        Database::pdo()->prepare(
            'UPDATE utilisateur
             SET nom = :nom, prenom = :prenom, telephone = :tel, adresse_postale = :adresse, code_postal = :cp, ville = :ville
             WHERE id = :id'
        )->execute([
            'nom' => $d['nom'],
            'prenom' => $d['prenom'],
            'tel' => $d['telephone'],
            'adresse' => $d['adresse_postale'],
            'cp' => $d['code_postal'],
            'ville' => $d['ville'],
            'id' => $id,
        ]);
    }
}
