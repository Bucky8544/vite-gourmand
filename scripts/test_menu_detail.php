<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Repository\MenuRepository;

$echecs = 0;
function verifier(string $libelle, mixed $obtenu, mixed $attendu): void
{
    global $echecs;
    $ok = $obtenu === $attendu;
    echo ($ok ? '✅ ' : '❌ ') . $libelle;
    if (!$ok) {
        $echecs++;
        echo ' : obtenu ' . json_encode($obtenu, JSON_UNESCAPED_UNICODE) . ', attendu ' . json_encode($attendu, JSON_UNESCAPED_UNICODE);
    }
    echo PHP_EOL;
}

$repo = new MenuRepository();
$titres = static fn (array $plats): array => array_column($plats, 'titre');

echo "--- Menu 1\n";
$m = $repo->find(1);
verifier('Titre', $m['titre'], 'Menu de Noël traditionnel');
verifier('Thème', $m['theme'], 'Noël');
verifier('Régime', $m['regime'], 'Classique');
verifier('Nombre de personnes minimum', (int) $m['nombre_personne_minimum'], 6);
verifier('Prix pour le minimum', (float) $m['prix_minimum'], 150.0);
verifier('Stock lisible', (int) $m['stock'] >= 0, true);
verifier('Conditions renseignées', str_starts_with($m['conditions'], 'Commande à passer au moins 14 jours'), true);

echo "--- Menus introuvables\n";
verifier('Identifiant inconnu', $repo->find(99999), null);
verifier('Identifiant 0', $repo->find(0), null);
$pdo = Database::pdo();
$pdo->beginTransaction();
try {
    $pdo->exec('UPDATE menu SET actif = 0 WHERE id = 1');
    verifier('Menu désactivé : introuvable', $repo->find(1), null);
} finally {
    $pdo->rollBack();
}
verifier('Menu réactivé (transaction annulée) : retrouvé', $repo->find(1)['titre'] ?? null, 'Menu de Noël traditionnel');

echo "--- Images\n";
$images = $repo->images(1);
verifier('2 images pour le menu 1', count($images), 2);
verifier('Première image (position 0)', $images[0]['url'], '/assets/img/menus/noel-1.jpg');
verifier('Texte alternatif présent', $images[0]['texte_alternatif'], 'Table de Noël dressée avec une dinde rôtie');
verifier('1 image pour le menu 2', count($repo->images(2)), 1);

echo "--- Composition et allergènes\n";
$c = $repo->composition(1);
verifier('Entrées du menu 1', $titres($c['entree']), ['Foie gras maison et chutney de figues', 'Velouté de potimarron']);
verifier('Plat du menu 1', $titres($c['plat']), ['Dinde rôtie aux marrons']);
verifier('Dessert du menu 1', $titres($c['dessert']), ['Bûche de Noël chocolat-marron']);
verifier('Allergènes du foie gras', $c['entree'][0]['allergenes'], ['Gluten', 'Sulfites']);
verifier('Allergènes du velouté', $c['entree'][1]['allergenes'], ['Lait']);
verifier('Allergènes de la dinde', $c['plat'][0]['allergenes'], ['Céleri']);
verifier('Allergènes de la bûche', $c['dessert'][0]['allergenes'], ['Gluten', 'Œufs', 'Lait', 'Fruits à coque']);

$v = $repo->composition(4);
verifier('Menu vegan : plat sans allergène déclaré', $v['plat'][0]['allergenes'], []);
verifier('Menu vegan : dessert', $v['dessert'][0]['allergenes'], ['Gluten']);

$k = $repo->composition(5);
verifier('Menu cocktail : verrines', $k['entree'][0]['allergenes'], ['Poisson', 'Lait']);
verifier('Menu cocktail : mini-burgers', $k['plat'][0]['allergenes'], ['Gluten', 'Moutarde', 'Graines de sésame']);

$vide = $repo->composition(99999);
verifier('Menu inconnu : trois groupes vides', $vide, ['entree' => [], 'plat' => [], 'dessert' => []]);

echo $echecs === 0 ? "\nTous les tests passent.\n" : "\n$echecs test(s) en échec.\n";
exit($echecs === 0 ? 0 : 1);
