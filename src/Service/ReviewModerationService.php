<?php
declare(strict_types=1);

namespace App\Service;

use App\Core\Database;
use DateTimeImmutable;
use Throwable;

final class ReviewModerationService
{
    public const DECISIONS = ['valide', 'refuse'];

    /**
     * Valide (publie) ou refuse un avis, ou revient sur une décision précédente.
     * L'avis est verrouillé : deux employés ne peuvent pas décider en même temps.
     *
     * @throws OrderException decision, introuvable ou statut (déjà dans cet état)
     */
    public function decide(int $staffId, int $avisId, string $decision, ?DateTimeImmutable $now = null): void
    {
        if (!in_array($decision, self::DECISIONS, true)) {
            throw new OrderException('decision', 'Décision inconnue.');
        }

        $now ??= new DateTimeImmutable('now');
        $pdo = Database::pdo();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('SELECT id, statut FROM avis WHERE id = :id FOR UPDATE');
            $stmt->execute(['id' => $avisId]);
            $avis = $stmt->fetch();

            if (!$avis) {
                throw new OrderException('introuvable', 'Avis introuvable.');
            }
            if ($avis['statut'] === $decision) {
                throw new OrderException('statut', $decision === 'valide'
                    ? 'Cet avis est déjà validé (un autre membre de l\'équipe l\'a peut-être fait entre-temps).'
                    : 'Cet avis est déjà refusé (un autre membre de l\'équipe l\'a peut-être fait entre-temps).');
            }

            $pdo->prepare('UPDATE avis SET statut = :s, modere_par = :u, modere_at = :d WHERE id = :id')
                ->execute(['s' => $decision, 'u' => $staffId, 'd' => $now->format('Y-m-d H:i:s'), 'id' => $avisId]);

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
