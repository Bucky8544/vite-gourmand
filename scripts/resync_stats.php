<?php
declare(strict_types=1);

// Reconstruit la collection MongoDB « commandes_stats » à partir de MySQL (commandes acceptées, non annulées).
// À lancer après une installation neuve ou une panne de MongoDB :
//   docker compose exec web php scripts/resync_stats.php --oui
require __DIR__ . '/../vendor/autoload.php';

use App\Service\StatsSyncService;

if (!in_array('--oui', $argv, true)) {
    echo "Ce script recrée toute la collection commandes_stats à partir de MySQL.\nRelancez-le avec --oui pour confirmer.\n";
    exit(1);
}

echo (new StatsSyncService())->resync() . " commande(s) synchronisée(s).\n";
