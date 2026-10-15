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
            'SELECT c.*, m.titre AS menu_titre
             FROM commande c
             JOIN menu m ON m.id = c.menu_id
             WHERE c.numero_commande = :n AND c.utilisateur_id = :u'
        );
        $stmt->execute(['n' => $numero, 'u' => $userId]);

        return $stmt->fetch() ?: null;
    }
}
