<?php
declare(strict_types=1);

namespace App\Service;

use App\Core\Database;
use DateTimeImmutable;
use PDOException;
use Throwable;

final class ReviewService
{
    /**
     * Enregistre l'avis d'un client sur une commande terminée. Il attend la validation d'un employé.
     *
     * @throws OrderException introuvable, statut ou avis (déjà donné)
     */
    public function submit(int $userId, string $numero, int $note, string $commentaire, ?DateTimeImmutable $now = null): void
    {
        $now ??= new DateTimeImmutable('now');
        $pdo = Database::pdo();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('SELECT id, statut FROM commande WHERE numero_commande = :n AND utilisateur_id = :u FOR UPDATE');
            $stmt->execute(['n' => $numero, 'u' => $userId]);
            $commande = $stmt->fetch();

            if (!$commande) {
                throw new OrderException('introuvable', 'Commande introuvable.');
            }
            if ($commande['statut'] !== 'terminee') {
                throw new OrderException('statut', 'Vous pourrez donner votre avis une fois la commande terminée.');
            }

            try {
                $pdo->prepare("INSERT INTO avis (commande_id, note, commentaire, statut, created_at)
                               VALUES (:c, :note, :commentaire, 'en_attente', :d)")
                    ->execute(['c' => $commande['id'], 'note' => $note, 'commentaire' => $commentaire, 'd' => $now->format('Y-m-d H:i:s')]);
            } catch (PDOException $e) {
                if (($e->errorInfo[1] ?? null) === 1062) { // un seul avis par commande (clé unique)
                    throw new OrderException('avis', 'Vous avez déjà donné votre avis pour cette commande.');
                }
                throw $e;
            }

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
