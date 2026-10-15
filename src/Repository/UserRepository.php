<?php
declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;

final class UserRepository
{
    /** Crée un compte avec le rôle « utilisateur ». Lève une PDOException si l'e-mail existe déjà. */
    public function createUser(array $d, string $hash): int
    {
        $sql = 'INSERT INTO utilisateur
                  (email, mot_de_passe, nom, prenom, telephone, adresse_postale, code_postal, ville,
                   role_id, actif, consentement_rgpd_at)
                VALUES
                  (:email, :hash, :nom, :prenom, :telephone, :adresse, :cp, :ville,
                   (SELECT id FROM role WHERE libelle = :role), 1, NOW())';

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute([
            'email' => $d['email'],
            'hash' => $hash,
            'nom' => $d['nom'],
            'prenom' => $d['prenom'],
            'telephone' => $d['telephone'],
            'adresse' => $d['adresse_postale'],
            'cp' => $d['code_postal'],
            'ville' => $d['ville'],
            'role' => 'utilisateur',
        ]);

        return (int) Database::pdo()->lastInsertId();
    }
}
