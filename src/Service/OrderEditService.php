<?php
declare(strict_types=1);

namespace App\Service;

use App\Core\Database;
use DateTimeImmutable;
use PDO;
use Throwable;

/** Modification et annulation d'une commande par son client, tant qu'elle est « en attente ». */
final class OrderEditService
{
    public const MESSAGE_PRISE_EN_CHARGE = 'Cette commande a déjà été prise en charge par notre équipe : '
        . 'elle ne peut plus être modifiée ni annulée en ligne. Contactez-nous pour toute demande.';

    /**
     * @param array<string,mixed> $in données validées (personnes, date_prestation, heure_livraison, adresse_*, code_postal_*, ville_*)
     * @param array{km10:int,bordeaux:bool,estime:bool} $distance
     * @return array{devis: array<string,int>}
     * @throws OrderException introuvable, statut, minimum, delai ou prix
     */
    public function modify(int $userId, string $numero, array $in, array $distance, ?int $totalAttendu = null, ?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable('now');
        $pdo = Database::pdo();
        $pdo->beginTransaction();

        try {
            $commande = $this->verrouiller($pdo, $userId, $numero);
            if ($commande['statut'] !== 'en_attente') {
                throw new OrderException('statut', self::MESSAGE_PRISE_EN_CHARGE);
            }

            $stmt = $pdo->prepare('SELECT nombre_personne_minimum, prix_minimum, delai_commande_jours FROM menu WHERE id = :id');
            $stmt->execute(['id' => $commande['menu_id']]);
            $menu = $stmt->fetch();

            $minimum = (int) $menu['nombre_personne_minimum'];
            if ((int) $in['personnes'] < $minimum) {
                throw new OrderException('minimum', "Ce menu se commande pour $minimum personnes minimum.");
            }

            $aujourdhui = $now->setTime(0, 0);
            if ($in['date_prestation'] < $aujourdhui->format('Y-m-d')) {
                throw new OrderException('delai', 'La date de la prestation ne peut pas être dans le passé.');
            }
            // Le délai du menu ne s'applique que si le client change la date
            if ($in['date_prestation'] !== $commande['date_prestation']) {
                $auPlusTot = $aujourdhui->modify('+' . (int) $menu['delai_commande_jours'] . ' days');
                if ($in['date_prestation'] < $auPlusTot->format('Y-m-d')) {
                    throw new OrderException('delai', 'Ce menu doit être commandé plus tôt avant la prestation : choisissez une date à partir du '
                        . $auPlusTot->format('d/m/Y') . '.');
                }
            }

            $devis = Pricing::devis(Pricing::toCents($menu['prix_minimum']), $minimum, (int) $in['personnes'],
                $distance['bordeaux'], $distance['km10']);
            if ($totalAttendu !== null && $totalAttendu !== $devis['total']) {
                throw new OrderException('prix', 'Le prix de votre commande a changé. Vérifiez le détail avant de confirmer.');
            }

            $pdo->prepare(
                'UPDATE commande
                 SET date_prestation = :date, heure_livraison = :heure, adresse_prestation = :adresse,
                     code_postal_prestation = :cp, ville_prestation = :ville, distance_km = :distance,
                     nombre_personne = :nb, prix_menu = :prix_menu, remise = :remise,
                     prix_livraison = :livraison, prix_total = :total
                 WHERE id = :id'
            )->execute([
                'date' => $in['date_prestation'],
                'heure' => $in['heure_livraison'] . ':00',
                'adresse' => $in['adresse_prestation'],
                'cp' => $in['code_postal_prestation'],
                'ville' => $in['ville_prestation'],
                'distance' => number_format($distance['km10'] / 10, 1, '.', ''),
                'nb' => $in['personnes'],
                'prix_menu' => $this->euros($devis['menu']),
                'remise' => $this->euros($devis['remise']),
                'livraison' => $this->euros($devis['livraison']),
                'total' => $this->euros($devis['total']),
                'id' => $commande['id'],
            ]);

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        return ['devis' => $devis];
    }

    /**
     * Annule la commande, enregistre le changement de statut et rend l'unité de stock au menu.
     *
     * @throws OrderException introuvable ou statut
     */
    public function cancel(int $userId, string $numero, ?DateTimeImmutable $now = null): void
    {
        $now ??= new DateTimeImmutable('now');
        $pdo = Database::pdo();
        $pdo->beginTransaction();

        try {
            $commande = $this->verrouiller($pdo, $userId, $numero);
            if ($commande['statut'] !== 'en_attente') {
                throw new OrderException('statut', self::MESSAGE_PRISE_EN_CHARGE);
            }

            $pdo->prepare("UPDATE commande SET statut = 'annulee', motif_annulation = :motif WHERE id = :id")
                ->execute(['motif' => 'Annulation demandée par le client', 'id' => $commande['id']]);
            $pdo->prepare("INSERT INTO commande_statut_historique (commande_id, statut, modifie_par, date_modification)
                           VALUES (:c, 'annulee', :u, :d)")
                ->execute(['c' => $commande['id'], 'u' => $userId, 'd' => $now->format('Y-m-d H:i:s')]);
            $pdo->prepare('UPDATE menu SET stock = stock + 1 WHERE id = :id')->execute(['id' => $commande['menu_id']]);

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /** Verrouille la commande (le statut lu est donc celui qui sera modifié) et vérifie son propriétaire. */
    private function verrouiller(PDO $pdo, int $userId, string $numero): array
    {
        $stmt = $pdo->prepare(
            'SELECT id, statut, menu_id, date_prestation FROM commande
             WHERE numero_commande = :n AND utilisateur_id = :u FOR UPDATE'
        );
        $stmt->execute(['n' => $numero, 'u' => $userId]);
        $commande = $stmt->fetch();

        if (!$commande) {
            throw new OrderException('introuvable', 'Commande introuvable.');
        }

        return $commande;
    }

    private function euros(int $centimes): string
    {
        return number_format($centimes / 100, 2, '.', '');
    }
}
