<?php
declare(strict_types=1);

namespace App\Service;

use App\Core\Database;
use DateTimeImmutable;
use Throwable;

final class OrderService
{
    /**
     * Enregistre une commande dans une transaction : le menu est verrouillé, le stock vérifié puis décrémenté,
     * le prix recalculé côté serveur (jamais repris du navigateur), l'historique des statuts initialisé.
     *
     * @param array<string,mixed> $in données validées
     * @param array{km10:int,bordeaux:bool,estime:bool} $distance
     * @return array{id:int,numero:string,menu_titre:string,devis:array<string,int>}
     * @throws OrderException
     */
    public function place(int $userId, array $in, array $distance, ?int $totalAttendu = null, ?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable('now');
        $pdo = Database::pdo();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'SELECT id, titre, nombre_personne_minimum, prix_minimum, stock, delai_commande_jours
                 FROM menu WHERE id = :id AND actif = 1 FOR UPDATE'
            );
            $stmt->execute(['id' => $in['menu_id']]);
            $menu = $stmt->fetch();

            if (!$menu) {
                throw new OrderException('menu', "Ce menu n'est plus proposé.");
            }
            if ((int) $menu['stock'] <= 0) {
                throw new OrderException('stock', "Ce menu n'est plus disponible pour le moment.");
            }

            $minimum = (int) $menu['nombre_personne_minimum'];
            if ((int) $in['personnes'] < $minimum) {
                throw new OrderException('minimum', "Ce menu se commande pour $minimum personnes minimum.");
            }

            $auPlusTot = $now->setTime(0, 0)->modify('+' . (int) $menu['delai_commande_jours'] . ' days')->format('Y-m-d');
            if ($in['date_prestation'] < $auPlusTot) {
                throw new OrderException('delai', 'Ce menu doit être commandé plus tôt avant la prestation : choisissez une date à partir du '
                    . (new DateTimeImmutable($auPlusTot))->format('d/m/Y') . '.');
            }

            $devis = Pricing::devis(Pricing::toCents($menu['prix_minimum']), $minimum, (int) $in['personnes'],
                $distance['bordeaux'], $distance['km10']);
            if ($totalAttendu !== null && $totalAttendu !== $devis['total']) {
                throw new OrderException('prix', 'Le prix de votre commande a changé. Vérifiez le détail avant de confirmer.');
            }

            $pdo->prepare(
                "INSERT INTO commande
                   (numero_commande, utilisateur_id, menu_id, date_commande, date_prestation, heure_livraison,
                    adresse_prestation, code_postal_prestation, ville_prestation, distance_km, nombre_personne,
                    prix_menu, remise, prix_livraison, prix_total, statut, conditions_acceptees_at)
                 VALUES
                   (:numero, :user, :menu, :date_commande, :date_prestation, :heure,
                    :adresse, :cp, :ville, :distance, :nb,
                    :prix_menu, :remise, :livraison, :total, 'en_attente', :conditions)"
            )->execute([
                'numero' => 'TMP-' . bin2hex(random_bytes(7)),
                'user' => $userId,
                'menu' => $in['menu_id'],
                'date_commande' => $now->format('Y-m-d H:i:s'),
                'date_prestation' => $in['date_prestation'],
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
                'conditions' => $now->format('Y-m-d H:i:s'),
            ]);

            $id = (int) $pdo->lastInsertId();
            $numero = sprintf('VG-%s-%06d', $now->format('Y'), $id);
            $pdo->prepare('UPDATE commande SET numero_commande = :n WHERE id = :id')->execute(['n' => $numero, 'id' => $id]);

            $pdo->prepare(
                "INSERT INTO commande_statut_historique (commande_id, statut, modifie_par, date_modification)
                 VALUES (:c, 'en_attente', :u, :d)"
            )->execute(['c' => $id, 'u' => $userId, 'd' => $now->format('Y-m-d H:i:s')]);

            $maj = $pdo->prepare('UPDATE menu SET stock = stock - 1 WHERE id = :id AND stock > 0');
            $maj->execute(['id' => $in['menu_id']]);
            if ($maj->rowCount() !== 1) {
                throw new OrderException('stock', "Ce menu n'est plus disponible pour le moment.");
            }

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        return ['id' => $id, 'numero' => $numero, 'menu_titre' => (string) $menu['titre'], 'devis' => $devis];
    }

    private function euros(int $centimes): string
    {
        return number_format($centimes / 100, 2, '.', '');
    }
}
