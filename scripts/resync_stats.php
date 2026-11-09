<?php
declare(strict_types=1);

// Reconstruit la collection MongoDB « commandes_stats » à partir de MySQL (commandes acceptées et non annulées).
// À lancer après une panne de MongoDB : docker compose exec web php scripts/resync_stats.php --oui
require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Service\Pricing;
use App\Service\StatsRepository;

if (!in_array('--oui', $argv, true)) {
    echo "Ce script recrée toute la collection commandes_stats à partir de MySQL.\nRelancez-le avec --oui pour confirmer.\n";
    exit(1);
}

$client = new MongoDB\Client(getenv('MONGO_URI') ?: 'mongodb://mongo:27017');
$collection = $client->selectCollection(getenv('MONGO_DB') ?: 'vite_gourmand', 'commandes_stats');
$collection->deleteMany([]);

$stats = new StatsRepository($collection);
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
echo "$n commande(s) synchronisée(s).\n";
