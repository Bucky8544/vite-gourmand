<?php
declare(strict_types=1);

namespace App\Service;

final class OrderStatus
{
    public const LABELS = [
        'en_attente' => 'En attente de validation',
        'accepte' => 'Acceptée',
        'en_preparation' => 'En préparation',
        'en_cours_livraison' => 'En cours de livraison',
        'livre' => 'Livrée',
        'attente_retour_materiel' => 'En attente du retour de matériel',
        'terminee' => 'Terminée',
        'annulee' => 'Annulée',
    ];

    /** Étapes suivantes possibles (hors annulation). */
    public const SUIVANTS = [
        'en_attente' => ['accepte'],
        'accepte' => ['en_preparation'],
        'en_preparation' => ['en_cours_livraison'],
        'en_cours_livraison' => ['livre'],
        'livre' => ['terminee', 'attente_retour_materiel'],
        'attente_retour_materiel' => ['terminee'],
    ];

    /** Une commande ne s'annule plus une fois livrée. */
    public const ANNULABLES = ['en_attente', 'accepte', 'en_preparation', 'en_cours_livraison'];

    public const ACTIONS = [
        'accepte' => 'Accepter la commande',
        'en_preparation' => 'Passer en préparation',
        'en_cours_livraison' => 'Passer en cours de livraison',
        'livre' => 'Marquer comme livrée',
        'terminee' => 'Marquer comme terminée',
        'attente_retour_materiel' => 'Passer en attente du retour de matériel',
    ];

    public static function label(string $statut): string
    {
        return self::LABELS[$statut] ?? $statut;
    }

    /** Le client peut modifier ou annuler tant qu'aucun employé n'a accepté la commande. */
    public static function modifiable(string $statut): bool
    {
        return $statut === 'en_attente';
    }

    /**
     * Le suivi n'est visible qu'une fois la commande acceptée par l'équipe.
     *
     * @param list<array{statut:string}> $historique
     */
    public static function suiviVisible(array $historique): bool
    {
        foreach ($historique as $ligne) {
            if ($ligne['statut'] === 'accepte') {
                return true;
            }
        }

        return false;
    }

    /**
     * Statuts que l'équipe peut choisir depuis $statut. Après « livrée », la suite dépend du prêt de matériel :
     * terminée s'il n'y en a pas, attente du retour sinon.
     *
     * @return list<string>
     */
    public static function suivants(string $statut, bool $pretMateriel = false): array
    {
        if ($statut === 'livre') {
            return [$pretMateriel ? 'attente_retour_materiel' : 'terminee'];
        }

        return self::SUIVANTS[$statut] ?? [];
    }

    public static function annulable(string $statut): bool
    {
        return in_array($statut, self::ANNULABLES, true);
    }

    public static function estFinal(string $statut): bool
    {
        return $statut === 'terminee' || $statut === 'annulee';
    }
}
