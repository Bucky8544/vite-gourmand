<?php
declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;

final class OrderRepository
{
    /** Une commande n'est lisible que par son propriétaire (le filtre sur l'utilisateur est obligatoire). */
    public function findByNumero(string $numero, int $userId): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT c.*, m.titre AS menu_titre, m.nombre_personne_minimum, m.prix_minimum,
                    m.conditions AS menu_conditions, m.delai_commande_jours
             FROM commande c
             JOIN menu m ON m.id = c.menu_id
             WHERE c.numero_commande = :n AND c.utilisateur_id = :u'
        );
        $stmt->execute(['n' => $numero, 'u' => $userId]);

        return $stmt->fetch() ?: null;
    }

    /** @return list<array<string,mixed>> les plus récentes d'abord (200 au maximum) */
    public function listForUser(int $userId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT c.id, c.numero_commande, c.date_commande, c.date_prestation, c.nombre_personne, c.prix_total, c.statut,
                    m.titre AS menu_titre
             FROM commande c
             JOIN menu m ON m.id = c.menu_id
             WHERE c.utilisateur_id = :u
             ORDER BY c.date_commande DESC, c.id DESC
             LIMIT 200'
        );
        $stmt->execute(['u' => $userId]);

        return $stmt->fetchAll();
    }

    /** @return list<array{statut:string,date_modification:string}> du plus ancien au plus récent */
    public function history(int $commandeId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT statut, date_modification FROM commande_statut_historique
             WHERE commande_id = :c ORDER BY date_modification, id'
        );
        $stmt->execute(['c' => $commandeId]);

        return $stmt->fetchAll();
    }

    public function avis(int $commandeId): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT note, commentaire, statut, created_at FROM avis WHERE commande_id = :c');
        $stmt->execute(['c' => $commandeId]);

        return $stmt->fetch() ?: null;
    }
}
