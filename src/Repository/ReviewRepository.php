<?php
declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;

final class ReviewRepository
{
    public const PAR_PAGE = 20;
    public const STATUTS = ['en_attente' => 'En attente', 'valide' => 'Validés', 'refuse' => 'Refusés'];

    /**
     * Avis d'un statut, avec la commande, le client et le modérateur (espace employé).
     * File d'attente : les plus anciens d'abord. Autres onglets : les plus récents d'abord.
     *
     * @return array{lignes: list<array<string,mixed>>, total: int}
     */
    public function search(string $statut, int $page = 1): array
    {
        $pdo = Database::pdo();

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM avis WHERE statut = :s');
        $stmt->execute(['s' => $statut]);
        $total = (int) $stmt->fetchColumn();

        $ordre = $statut === 'en_attente' ? 'a.created_at ASC, a.id ASC' : 'a.created_at DESC, a.id DESC';
        $decalage = max(0, min($page, 10000) - 1) * self::PAR_PAGE; // entiers : jamais de saisie dans LIMIT
        $stmt = $pdo->prepare(
            'SELECT a.id, a.note, a.commentaire, a.statut, a.created_at, a.modere_at,
                    c.numero_commande, m.titre AS menu_titre, u.prenom, u.nom, u.email,
                    mo.prenom AS moderateur_prenom, mo.nom AS moderateur_nom, mo.email AS moderateur_email
             FROM avis a
             JOIN commande c ON c.id = a.commande_id
             JOIN utilisateur u ON u.id = c.utilisateur_id
             JOIN menu m ON m.id = c.menu_id
             LEFT JOIN utilisateur mo ON mo.id = a.modere_par
             WHERE a.statut = :s
             ORDER BY ' . $ordre . '
             LIMIT ' . self::PAR_PAGE . ' OFFSET ' . (int) $decalage
        );
        $stmt->execute(['s' => $statut]);

        return ['lignes' => $stmt->fetchAll(), 'total' => $total];
    }

    /** @return array<string,int> nombre d'avis par statut */
    public function countByStatus(): array
    {
        $compte = array_fill_keys(array_keys(self::STATUTS), 0);
        foreach (Database::pdo()->query('SELECT statut, COUNT(*) AS n FROM avis GROUP BY statut')->fetchAll() as $ligne) {
            $compte[$ligne['statut']] = (int) $ligne['n'];
        }

        return $compte;
    }

    /**
     * Avis validés pour la page d'accueil, du plus récent au plus ancien.
     * Données publiques uniquement : le nom du client est réduit à son prénom et à l'initiale du nom.
     *
     * @return list<array{auteur:string,note:int,commentaire:string,date:string}>
     */
    public function validatedForHome(int $limite = 6): array
    {
        $limite = max(1, min($limite, 24));
        $lignes = Database::pdo()->query(
            "SELECT a.note, a.commentaire, a.created_at, u.prenom, u.nom
             FROM avis a
             JOIN commande c ON c.id = a.commande_id
             JOIN utilisateur u ON u.id = c.utilisateur_id
             WHERE a.statut = 'valide'
             ORDER BY a.created_at DESC, a.id DESC
             LIMIT $limite"
        )->fetchAll();

        return array_map(static fn (array $l): array => [
            'auteur' => self::auteur($l['prenom'], $l['nom']),
            'note' => (int) $l['note'],
            'commentaire' => (string) $l['commentaire'],
            'date' => (string) $l['created_at'],
        ], $lignes);
    }

    /** @return array{count:int,moyenne:float} sur les avis validés */
    public function statsValidated(): array
    {
        $l = Database::pdo()->query("SELECT COUNT(*) AS n, AVG(note) AS moyenne FROM avis WHERE statut = 'valide'")->fetch();

        return ['count' => (int) $l['n'], 'moyenne' => $l['moyenne'] !== null ? (float) $l['moyenne'] : 0.0];
    }

    /** « Camille D. » : prénom et initiale du nom. */
    public static function auteur(?string $prenom, ?string $nom): string
    {
        $prenom = trim((string) $prenom);
        $nom = trim((string) $nom);
        if ($prenom === '') {
            return 'Un client';
        }

        return $nom === '' ? $prenom : $prenom . ' ' . mb_strtoupper(mb_substr($nom, 0, 1)) . '.';
    }
}
