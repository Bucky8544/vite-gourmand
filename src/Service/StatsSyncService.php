<?php
declare(strict_types=1);

namespace App\Service;

use App\Core\Database;
use DateTimeImmutable;
use MongoDB\Client;
use MongoDB\Collection;

/** Reconstruit les statistiques MongoDB à partir de MySQL (commandes acceptées et au-delà, hors annulées). */
final class StatsSyncService
{
    /** @return int nombre de commandes synchronisées */
    public function resync(?Collection $collection = null): int
    {
        $collection ??= (new Client(getenv('MONGO_URI') ?: 'mongodb://mongo:27017', ['serverSelectionTimeoutMS' => 3000, 'connectTimeoutMS' => 3000]))
            ->selectCollection(getenv('MONGO_DB') ?: 'vite_gourmand', 'commandes_stats');

        $stats = new StatsRepository($collection);
        $collection->deleteMany([]);
        $stats->creerIndex();

        $lignes = Database::pdo()->query(
            "SELECT c.id, c.menu_id, m.titre, c.date_prestation, c.nombre_personne, c.prix_total
             FROM commande c JOIN menu m ON m.id = c.menu_id
             WHERE c.statut NOT IN ('en_attente', 'annulee')
             ORDER BY c.id"
        );

        $n = 0;
        foreach ($lignes as $l) {
            $stats->enregistrer((int) $l['id'], (int) $l['menu_id'], (string) $l['titre'], new DateTimeImmutable($l['date_prestation']),
                (int) $l['nombre_personne'], Pricing::toCents($l['prix_total']));
            $n++;
        }

        return $n;
    }
}
