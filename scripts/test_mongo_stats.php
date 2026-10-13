<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Service\StatsRepository;
use MongoDB\Client;

$echecs = 0;

function verifier(string $libelle, mixed $obtenu, mixed $attendu): void
{
    global $echecs;
    $ok = $obtenu === $attendu;
    echo ($ok ? '✅ ' : '❌ ') . $libelle;
    if (!$ok) {
        $echecs++;
        echo ' : obtenu ' . json_encode($obtenu) . ', attendu ' . json_encode($attendu);
    }
    echo PHP_EOL;
}

$client = new Client(getenv('MONGO_URI') ?: 'mongodb://mongo:27017');
$collection = $client->selectCollection(getenv('MONGO_DB') ?: 'vite_gourmand', 'commandes_stats_test');
$repo = new StatsRepository($collection);

try {
    $repo->creerIndex();

    $repo->enregistrer(1, 1, 'Menu de Noël traditionnel', new DateTimeImmutable('2026-12-20'), 8, 20000);
    $repo->enregistrer(2, 1, 'Menu de Noël traditionnel', new DateTimeImmutable('2026-12-24'), 6, 15000);
    $repo->enregistrer(3, 2, 'Menu de Pâques', new DateTimeImmutable('2027-04-05'), 4, 11200);
    $repo->enregistrer(4, 3, 'Menu végétarien gourmand', new DateTimeImmutable('2026-11-10'), 4, 8400);
    // Même commande écrite deux fois : aucun doublon attendu
    $repo->enregistrer(1, 1, 'Menu de Noël traditionnel', new DateTimeImmutable('2026-12-20'), 8, 20000);

    verifier('Pas de doublon (upsert)', $collection->countDocuments(), 4);

    verifier(
        'Commandes par menu',
        array_column($repo->commandesParMenu(), 'nombre_commandes', 'menu_id'),
        [1 => 2, 2 => 1, 3 => 1]
    );
    verifier(
        'Chiffre d\'affaires du menu 1',
        $repo->chiffreAffaires(1),
        ['nombre_commandes' => 2, 'chiffre_affaires_centimes' => 35000]
    );
    verifier(
        'Chiffre d\'affaires total',
        $repo->chiffreAffaires(),
        ['nombre_commandes' => 4, 'chiffre_affaires_centimes' => 54600]
    );

    $debut = new DateTimeImmutable('2026-12-01 00:00:00');
    $fin = new DateTimeImmutable('2026-12-31 23:59:59');
    verifier(
        'Chiffre d\'affaires de décembre 2026',
        $repo->chiffreAffaires(null, $debut, $fin),
        ['nombre_commandes' => 2, 'chiffre_affaires_centimes' => 35000]
    );
    verifier(
        'Menu 3 sur décembre 2026 (aucune commande)',
        $repo->chiffreAffaires(3, $debut, $fin),
        ['nombre_commandes' => 0, 'chiffre_affaires_centimes' => 0]
    );

    $repo->supprimer(4);
    verifier(
        'Après suppression de la commande 4',
        array_column($repo->commandesParMenu(), 'nombre_commandes', 'menu_id'),
        [1 => 2, 2 => 1]
    );
} finally {
    $collection->drop();
}

echo $echecs === 0 ? "\nTous les tests passent.\n" : "\n$echecs test(s) en échec.\n";
exit($echecs === 0 ? 0 : 1);
