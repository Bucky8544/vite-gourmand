<?php
declare(strict_types=1);

namespace App\Service;

use App\Core\Database;
use App\Validation\CancelValidator;
use DateTimeImmutable;
use PDO;
use Throwable;

/** Changements de statut faits par l'équipe. Chaque opération verrouille la commande : le statut lu est celui qui sera modifié. */
final class OrderStaffService
{
    /**
     * @throws OrderException introuvable ou transition
     */
    public function changeStatus(int $staffId, string $numero, string $cible, bool $pretMateriel = false, ?DateTimeImmutable $now = null): void
    {
        $now ??= new DateTimeImmutable('now');
        $pdo = Database::pdo();
        $pdo->beginTransaction();

        try {
            $c = $this->verrouiller($pdo, $numero);

            if (!in_array($cible, OrderStatus::suivants($c['statut'], (bool) $c['pret_materiel']), true)) {
                throw new OrderException('transition', 'Ce changement de statut est impossible : la commande est actuellement « '
                    . OrderStatus::label($c['statut']) . ' ».');
            }

            // Le prêt de matériel se déclare à la livraison ; la restitution, en quittant l'attente de retour
            $pret = $cible === 'livre' ? (int) $pretMateriel : (int) $c['pret_materiel'];
            $restitue = ($c['statut'] === 'attente_retour_materiel' && $cible === 'terminee') ? 1 : (int) $c['materiel_restitue'];

            $pdo->prepare('UPDATE commande SET statut = :s, pret_materiel = :p, materiel_restitue = :r WHERE id = :id')
                ->execute(['s' => $cible, 'p' => $pret, 'r' => $restitue, 'id' => $c['id']]);
            $this->historiser($pdo, (int) $c['id'], $cible, $staffId, $now);

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Annule une commande : le client doit avoir été contacté, le mode de contact et le motif sont conservés.
     * L'unité de stock est rendue au menu.
     *
     * @throws OrderException contact, motif, introuvable ou transition
     */
    public function cancel(int $staffId, string $numero, string $modeContact, string $motif, ?DateTimeImmutable $now = null): void
    {
        $now ??= new DateTimeImmutable('now');
        $motif = trim($motif);
        if (!in_array($modeContact, CancelValidator::MODES, true)) {
            throw new OrderException('contact', 'Indiquez comment le client a été contacté (GSM ou mail).');
        }
        if (mb_strlen($motif) < CancelValidator::MOTIF_MIN || mb_strlen($motif) > CancelValidator::MOTIF_MAX) {
            throw new OrderException('motif', "Le motif de l'annulation est obligatoire.");
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();

        try {
            $c = $this->verrouiller($pdo, $numero);
            if (!OrderStatus::annulable($c['statut'])) {
                throw new OrderException('transition', 'Cette commande ne peut plus être annulée : elle est « '
                    . OrderStatus::label($c['statut']) . ' ».');
            }

            $pdo->prepare("UPDATE commande SET statut = 'annulee', motif_annulation = :motif, mode_contact_annulation = :mode WHERE id = :id")
                ->execute(['motif' => $motif, 'mode' => $modeContact, 'id' => $c['id']]);
            $this->historiser($pdo, (int) $c['id'], 'annulee', $staffId, $now);
            $pdo->prepare('UPDATE menu SET stock = stock + 1 WHERE id = :id')->execute(['id' => $c['menu_id']]);

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    private function verrouiller(PDO $pdo, string $numero): array
    {
        $stmt = $pdo->prepare('SELECT id, statut, menu_id, pret_materiel, materiel_restitue FROM commande WHERE numero_commande = :n FOR UPDATE');
        $stmt->execute(['n' => $numero]);
        $commande = $stmt->fetch();

        if (!$commande) {
            throw new OrderException('introuvable', 'Commande introuvable.');
        }

        return $commande;
    }

    private function historiser(PDO $pdo, int $commandeId, string $statut, int $staffId, DateTimeImmutable $now): void
    {
        $pdo->prepare('INSERT INTO commande_statut_historique (commande_id, statut, modifie_par, date_modification) VALUES (:c, :s, :u, :d)')
            ->execute(['c' => $commandeId, 's' => $statut, 'u' => $staffId, 'd' => $now->format('Y-m-d H:i:s')]);
    }
}
