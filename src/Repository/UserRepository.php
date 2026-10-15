<?php
declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;

final class UserRepository
{
    private const SELECT = 'SELECT u.id, u.email, u.mot_de_passe, u.nom, u.prenom, u.actif, r.libelle AS role
                            FROM utilisateur u
                            JOIN role r ON r.id = u.role_id';

    public function findByEmail(string $email): ?array
    {
        $stmt = Database::pdo()->prepare(self::SELECT . ' WHERE u.email = :email');
        $stmt->execute(['email' => $email]);

        return $stmt->fetch() ?: null;
    }

    /** Compte actif uniquement, sans le hash du mot de passe. */
    public function findActiveById(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(self::SELECT . ' WHERE u.id = :id AND u.actif = 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        unset($row['mot_de_passe']);

        return $row;
    }

    public function updatePasswordHash(int $id, string $hash): void
    {
        Database::pdo()
            ->prepare('UPDATE utilisateur SET mot_de_passe = :hash WHERE id = :id')
            ->execute(['hash' => $hash, 'id' => $id]);
    }

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
