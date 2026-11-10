<?php
declare(strict_types=1);

namespace App\Service;

use DateTimeImmutable;
use Throwable;

/**
 * Conséquences d'un changement de statut, appliquées APRÈS la transaction SQL : statistiques MongoDB et mails.
 * Une panne de l'un ou de l'autre est journalisée, mais ne défait jamais le changement de statut.
 */
final class OrderStaffEffects
{
    public function __construct(private StatsRepository $stats, private StaffMailer $mailer)
    {
    }

    public static function fromEnv(): self
    {
        return new self(StatsRepository::fromEnv(), new StaffMailer());
    }

    /** @param array<string,mixed> $c commande lue après le changement (avec les coordonnées du client) */
    public function apply(array $c, string $nouveauStatut): void
    {
        try {
            if ($nouveauStatut === 'accepte') {
                $this->stats->enregistrer((int) $c['id'], (int) $c['menu_id'], (string) $c['menu_titre'],
                    new DateTimeImmutable((string) $c['date_prestation']), (int) $c['nombre_personne'], Pricing::toCents($c['prix_total']));
            } elseif ($nouveauStatut === 'annulee') {
                $this->stats->supprimer((int) $c['id']);
            }
        } catch (Throwable $e) {
            error_log('Statistiques MongoDB non mises à jour (commande ' . ($c['numero_commande'] ?? '?') . ') : ' . $e->getMessage());
        }

        try {
            match ($nouveauStatut) {
                'terminee' => $this->mailer->sendReviewInvitation($c),
                'attente_retour_materiel' => $this->mailer->sendMaterialReminder($c),
                'annulee' => $this->mailer->sendCancellation($c),
                default => null,
            };
        } catch (Throwable $e) {
            error_log('Mail de changement de statut non envoyé (commande ' . ($c['numero_commande'] ?? '?') . ') : ' . $e->getMessage());
        }
    }
}
