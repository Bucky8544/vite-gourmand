<?php
declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;

/** Lecture des commandes pour l'équipe (employés et administrateur) : toutes les commandes, avec les coordonnées du client. */
final class StaffOrderRepository
{
    public const PAR_PAGE = 20;

    /**
     * @param array{statut?:string,q?:string} $f filtres : statut exact, et client (nom, prénom, e-mail) ou numéro de commande
     * @return array{lignes: list<array<string,mixed>>, total: int}
     */
    public function search(array $f, int $page = 1): array
    {
        [$where, $params] = $this->conditions($f);
        $pdo = Database::pdo();

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM commande c JOIN utilisateur u ON u.id = c.utilisateur_id WHERE ' . $where
        );
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        $decalage = max(0, min($page, 10000) - 1) * self::PAR_PAGE; // entiers : jamais de saisie dans LIMIT
        $stmt = $pdo->prepare(
            'SELECT c.id, c.numero_commande, c.date_commande, c.date_prestation, c.heure_livraison, c.nombre_personne,
                    c.prix_total, c.statut, u.nom, u.prenom, u.email, m.titre AS menu_titre
             FROM commande c
             JOIN utilisateur u ON u.id = c.utilisateur_id
             JOIN menu m ON m.id = c.menu_id
             WHERE ' . $where . "
             ORDER BY (c.statut IN ('terminee', 'annulee')) ASC, c.date_prestation ASC, c.id DESC
             LIMIT " . self::PAR_PAGE . ' OFFSET ' . (int) $decalage
        );
        $stmt->execute($params);

        return ['lignes' => $stmt->fetchAll(), 'total' => $total];
    }

    /** Commande avec les coordonnées du client. */
    public function find(string $numero): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT c.*, u.nom, u.prenom, u.email, u.telephone,
                    m.titre AS menu_titre, m.conditions AS menu_conditions
             FROM commande c
             JOIN utilisateur u ON u.id = c.utilisateur_id
             JOIN menu m ON m.id = c.menu_id
             WHERE c.numero_commande = :n'
        );
        $stmt->execute(['n' => $numero]);

        return $stmt->fetch() ?: null;
    }

    /** @return list<array<string,mixed>> historique avec l'auteur de chaque changement */
    public function history(int $commandeId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT h.statut, h.date_modification, a.prenom, a.nom, a.email
             FROM commande_statut_historique h
             LEFT JOIN utilisateur a ON a.id = h.modifie_par
             WHERE h.commande_id = :c
             ORDER BY h.date_modification, h.id'
        );
        $stmt->execute(['c' => $commandeId]);

        return $stmt->fetchAll();
    }

    /** @return array<string,int> nombre de commandes par statut */
    public function countsByStatus(): array
    {
        $compte = [];
        foreach (Database::pdo()->query('SELECT statut, COUNT(*) AS n FROM commande GROUP BY statut')->fetchAll() as $ligne) {
            $compte[$ligne['statut']] = (int) $ligne['n'];
        }

        return $compte;
    }

    /** @return array{0:string,1:array<string,string>} */
    private function conditions(array $f): array
    {
        $where = ['1 = 1'];
        $params = [];

        if (($f['statut'] ?? '') !== '') {
            $where[] = 'c.statut = :statut';
            $params['statut'] = $f['statut'];
        }
        if (($f['q'] ?? '') !== '') {
            $like = '%' . addcslashes($f['q'], '%_\\') . '%'; // les jokers saisis sont traités comme du texte
            $where[] = "(u.nom LIKE :q1 OR u.prenom LIKE :q2 OR u.email LIKE :q3
                         OR CONCAT(u.prenom, ' ', u.nom) LIKE :q4 OR c.numero_commande LIKE :q5)";
            foreach (['q1', 'q2', 'q3', 'q4', 'q5'] as $nom) {
                $params[$nom] = $like;
            }
        }

        return [implode(' AND ', $where), $params];
    }
}
