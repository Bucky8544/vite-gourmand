<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Service\StatsRepository;

StatsRepository::fromEnv()->creerIndex();
echo "Index MongoDB créés (commandes_stats)\n";
