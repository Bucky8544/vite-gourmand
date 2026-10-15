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
}
