<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Core\View;
use App\Repository\MenuRepository;
use App\Validation\MenuFilter;

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

$repo = new MenuRepository();
$ids = static fn (array $q): array => array_map(
    static fn (array $m): int => (int) $m['id'],
    $repo->search(MenuFilter::parse($q)['filters'])
);

echo "--- Données\n";
$premier = $repo->search()[0];
verifier('6 menus actifs', count($repo->search()), 6);
verifier('Thème du menu 1', $premier['theme'], 'Noël');
verifier('Régime du menu 1', $premier['regime'], 'Classique');
verifier('Prix du menu 1', (float) $premier['prix_minimum'], 150.0);
verifier('Image et texte alternatif présents', $premier['image_url'] !== null && $premier['image_alt'] !== '', true);

echo "--- Filtres de prix\n";
verifier('Prix max 100', $ids(['prix_max' => '100']), [3, 6]);
verifier('Prix max 150 (borne incluse)', $ids(['prix_max' => '150']), [1, 2, 3, 4, 6]);
verifier('Prix max 112,00 (virgule acceptée)', $ids(['prix_max' => '112,00']), [2, 3, 6]);
verifier('Fourchette 100 à 200', $ids(['prix_de' => '100', 'prix_a' => '200']), [1, 2, 4]);
verifier('Fourchette : minimum seul', $ids(['prix_de' => '200']), [5]);
verifier('Fourchette : maximum seul', $ids(['prix_a' => '80']), [6]);

echo "--- Thème, régime, personnes\n";
verifier('Thème Noël', $ids(['theme' => '1']), [1]);
verifier('Thème Classique', $ids(['theme' => '3']), [3, 6]);
verifier('Thème Événement', $ids(['theme' => '4']), [4, 5]);
verifier('Régime Végétarien', $ids(['regime' => '2']), [3]);
verifier('Régime Vegan', $ids(['regime' => '3']), [4]);
verifier('Régime Classique', $ids(['regime' => '1']), [1, 2, 5, 6]);
verifier('4 personnes', $ids(['personnes' => '4']), [2, 3, 6]);
verifier('6 personnes', $ids(['personnes' => '6']), [1, 2, 3, 4, 6]);
verifier('10 personnes', $ids(['personnes' => '10']), [1, 2, 3, 4, 5, 6]);
verifier('3 personnes (aucun menu)', $ids(['personnes' => '3']), []);

echo "--- Combinaisons\n";
verifier('Thème Classique et prix max 80', $ids(['theme' => '3', 'prix_max' => '80']), [6]);
verifier('Régime Classique et 6 personnes', $ids(['regime' => '1', 'personnes' => '6']), [1, 2, 6]);

echo "--- Menu désactivé (transaction annulée)\n";
$pdo = Database::pdo();
$pdo->beginTransaction();
try {
    $pdo->exec('UPDATE menu SET actif = 0 WHERE id = 1');
    verifier('Un menu désactivé n\'est plus listé', $ids([]), [2, 3, 4, 5, 6]);
} finally {
    $pdo->rollBack();
}

echo "--- Validation des saisies\n";
$e = static fn (array $q): array => MenuFilter::parse($q)['errors'];
verifier('Aucune erreur sans filtre', $e([]), []);
verifier('Champs vides ignorés', $e(['prix_max' => '', 'theme' => '  ']), []);
verifier('Prix non numérique refusé', isset($e(['prix_max' => 'abc'])['prix_max']), true);
verifier('Prix négatif refusé', isset($e(['prix_max' => '-5'])['prix_max']), true);
verifier('Tentative d\'injection SQL refusée (prix)', isset($e(['prix_max' => '1; DROP TABLE menu'])['prix_max']), true);
verifier('Tentative d\'injection SQL refusée (thème)', isset($e(['theme' => '1 OR 1=1'])['theme']), true);
verifier('Tableau refusé', isset($e(['theme' => ['1']])['theme']), true);
verifier('Personnes à 0 refusé', isset($e(['personnes' => '0'])['personnes']), true);
verifier('Personnes décimal refusé', isset($e(['personnes' => '2.5'])['personnes']), true);
verifier('Fourchette inversée refusée', isset($e(['prix_de' => '200', 'prix_a' => '100'])['prix_a']), true);
verifier('Filtre invalide ignoré : tous les menus', $ids(['prix_max' => 'abc']), [1, 2, 3, 4, 5, 6]);

echo "--- Images et prix\n";
$vue = new View();
verifier('Image absente : image de remplacement', $vue->menuImage('/assets/img/menus/n-existe-pas.jpg'), '/assets/img/placeholder-menu.svg');
verifier('Image null : image de remplacement', $vue->menuImage(null), '/assets/img/placeholder-menu.svg');
verifier('Remontée de dossier refusée', $vue->menuImage('/assets/../../.env'), '/assets/img/placeholder-menu.svg');
verifier('URL externe refusée', $vue->menuImage('https://exemple.fr/x.jpg'), '/assets/img/placeholder-menu.svg');
verifier('Image existante conservée', $vue->menuImage('/assets/img/placeholder-menu.svg'), '/assets/img/placeholder-menu.svg');
verifier('Prix entier', $vue->price('150.00'), "150\u{00A0}€");
verifier('Prix avec centimes', $vue->price(112.5), "112,50\u{00A0}€");

echo $echecs === 0 ? "\nTous les tests passent.\n" : "\n$echecs test(s) en échec.\n";
exit($echecs === 0 ? 0 : 1);
