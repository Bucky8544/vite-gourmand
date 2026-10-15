<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Core\Money;
use App\Repository\HoraireRepository;
use App\Repository\OrderRepository;
use App\Service\DistanceException;
use App\Service\DistanceService;
use App\Service\OrderException;
use App\Service\OrderService;
use App\Service\Pricing;
use App\Validation\OrderValidator;

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

// ================= Prix =================
echo "--- Prix du menu\n";
$prix = static fn (int $pm, int $min, int $n): array => [Pricing::prixMenuBrut($pm, $min, $n), Pricing::remise(Pricing::prixMenuBrut($pm, $min, $n), $min, $n)];
verifier('Menu 1, 6 personnes', $prix(15000, 6, 6), [15000, 0]);
verifier('Menu 1, 10 personnes : pas de remise', $prix(15000, 6, 10), [25000, 0]);
verifier('Menu 1, 11 personnes : remise de 10 %', $prix(15000, 6, 11), [27500, 2750]);
verifier('Menu 2, 9 personnes (4 + 5)', $prix(11200, 4, 9), [25200, 2520]);
verifier('Menu 5, 15 personnes', $prix(22000, 10, 15), [33000, 3300]);
verifier('Prix fractionnaire : 100 € / 3, 7 personnes', $prix(10000, 3, 7), [23333, 0]);
verifier('Prix fractionnaire : 100 € / 3, 8 personnes', $prix(10000, 3, 8), [26667, 2667]);
verifier('Arrondi de la moitié vers le haut', $prix(101, 2, 3), [152, 0]);
verifier('Seuil de remise', Pricing::seuilRemise(6), 11);
verifier('Euros vers centimes', [Pricing::toCents('150.00'), Pricing::toCents(112.5), Pricing::toCents('0.07')], [15000, 11250, 7]);

echo "--- Livraison\n";
verifier('Bordeaux : offerte', Pricing::livraison(true, 0), 0);
verifier('Bordeaux : offerte quelle que soit la distance', Pricing::livraison(true, 999), 0);
verifier('Hors Bordeaux, 0 km : forfait', Pricing::livraison(false, 0), 500);
verifier('Hors Bordeaux, 12,3 km', Pricing::livraison(false, 123), 1226);
verifier('Hors Bordeaux, 8,7 km', Pricing::livraison(false, 87), 1013);
verifier('Hors Bordeaux, 100 km', Pricing::livraison(false, 1000), 6400);
verifier('Hors Bordeaux, 0,1 km', Pricing::livraison(false, 1), 506);
verifier('Devis complet : 11 personnes, 12,3 km', Pricing::devis(15000, 6, 11, false, 123),
    ['brut' => 27500, 'remise' => 2750, 'menu' => 24750, 'livraison' => 1226, 'total' => 25976]);

echo "--- Argent\n";
verifier('12,26 €', Money::format(1226), "12,26\u{00A0}€");
verifier('0,05 €', Money::format(5), "0,05\u{00A0}€");
verifier('1 000,00 €', Money::format(100000), "1\u{202F}000,00\u{00A0}€");

// ================= Distance =================
echo "--- Distance (services simulés)\n";
$geo = static fn (float $lon, float $lat, string $insee, float $score = 0.9): string => json_encode([
    'type' => 'FeatureCollection',
    'features' => [['type' => 'Feature', 'geometry' => ['type' => 'Point', 'coordinates' => [$lon, $lat]],
        'properties' => ['score' => $score, 'citycode' => $insee, 'city' => 'Test']]],
]);
$service = static function (array $reponses, array &$appels) {
    $http = function (string $url) use ($reponses, &$appels): string {
        $appels[] = $url;
        foreach ($reponses as $motif => $reponse) {
            if (str_contains($url, $motif)) {
                if ($reponse instanceof Throwable) {
                    throw $reponse;
                }

                return $reponse;
            }
        }
        throw new RuntimeException('URL inattendue : ' . $url);
    };

    return new DistanceService($http(...), -0.5792, 44.8378);
};

$appels = [];
$r = $service(['geocodage' => $geo(-0.5731, 44.8412, '33063')], $appels)->compute('1 place de la Bourse', '33000', 'Bordeaux');
verifier('Bordeaux : distance nulle, pas de calcul d\'itinéraire', [$r, count($appels)], [['km10' => 0, 'bordeaux' => true, 'estime' => false], 1]);
verifier('URL du géocodage', str_starts_with($appels[0], 'https://data.geopf.fr/geocodage/search?') && str_contains($appels[0], 'postcode=33000'), true);

$appels = [];
$r = $service(['geocodage' => $geo(-0.7156, 44.8283, '33281'), 'navigation' => '{"distance": 8.7, "distanceUnit": "kilometer"}'], $appels)
    ->compute('Avenue Marcel Dassault', '33700', 'Mérignac');
verifier('Hors Bordeaux : 8,7 km par la route', [$r, count($appels)], [['km10' => 87, 'bordeaux' => false, 'estime' => false], 2]);
verifier('URL de l\'itinéraire', str_contains($appels[1], 'resource=bdtopo-osrm') && str_contains($appels[1], 'distanceUnit=kilometer'), true);

$appels = [];
$r = $service(['geocodage' => $geo(-0.7156, 44.8283, '33281'), 'navigation' => '{"distance": 8700, "distanceUnit": "meter"}'], $appels)->compute('x y z', '33700', 'Mérignac');
verifier('Distance reçue en mètres', $r['km10'], 87);

$appels = [];
$r = $service(['geocodage' => $geo(-0.7156, 44.8283, '33281'), 'navigation' => '{"distance": 8.7}'], $appels)->compute('x y z', '33700', 'Mérignac');
verifier('Distance sans unité (kilomètres)', $r['km10'], 87);

$appels = [];
$r = $service(['geocodage' => $geo(-0.7156, 44.8283, '33281'), 'navigation' => new RuntimeException('HTTP 500')], $appels)->compute('x y z', '33700', 'Mérignac');
verifier('Itinéraire en panne : distance estimée', [$r['estime'], $r['bordeaux'], $r['km10'] >= 130 && $r['km10'] <= 150], [true, false, true]);

$appels = [];
$r = $service(['geocodage' => $geo(-0.7156, 44.8283, '33281'), 'navigation' => '{"distance": 99999}'], $appels)->compute('x y z', '33700', 'Mérignac');
verifier('Distance aberrante : distance estimée', $r['estime'], true);

$appels = [];
$r = $service(['geocodage' => $geo(-0.7156, 44.8283, '33281'), 'navigation' => '{"foo": 1}'], $appels)->compute('x y z', '33700', 'Mérignac');
verifier('Réponse sans distance : distance estimée', $r['estime'], true);

foreach ([
    'Adresse introuvable' => ['geocodage' => '{"type":"FeatureCollection","features":[]}'],
    'Score trop faible' => ['geocodage' => $geo(-0.7, 44.8, '33281', 0.2)],
] as $libelle => $reponses) {
    $appels = [];
    try {
        $service($reponses, $appels)->compute('zzzz', '33000', 'Bordeaux');
        verifier($libelle . ' : refusée', false, true);
    } catch (DistanceException $e) {
        verifier($libelle . ' : refusée', str_contains($e->getMessage(), "pas trouvé"), true);
    }
}
$appels = [];
try {
    $service(['geocodage' => new RuntimeException('HTTP 503')], $appels)->compute('x', '33000', 'Bordeaux');
    verifier('Géocodage en panne : message clair', false, true);
} catch (DistanceException $e) {
    verifier('Géocodage en panne : message clair', str_contains($e->getMessage(), 'indisponible'), true);
}
$vol = DistanceService::haversineKm(44.8378, -0.5792, 48.8566, 2.3522);
verifier('Distance à vol d\'oiseau Bordeaux-Paris (≈ 499 km)', $vol > 497 && $vol < 502, true);

// ================= Validation =================
echo "--- Validation du formulaire\n";
$maintenant = new DateTimeImmutable('2026-10-15 10:00:00'); // un jeudi
$menu = ['id' => 1, 'nombre_personne_minimum' => 6, 'delai_commande_jours' => 14];
$horaires = static fn (int $jour): ?array => match (true) {
    $jour <= 5 => ['heure_ouverture' => '09:00:00', 'heure_fermeture' => '19:00:00', 'ferme' => 0],
    $jour === 6 => ['heure_ouverture' => '09:00:00', 'heure_fermeture' => '18:00:00', 'ferme' => 0],
    default => ['heure_ouverture' => '10:00:00', 'heure_fermeture' => '14:00:00', 'ferme' => 0],
};
$base = ['personnes' => '6', 'date_prestation' => '2026-11-20', 'heure_livraison' => '12:30',
    'adresse_prestation' => '12 rue Sainte-Catherine', 'code_postal_prestation' => '33000', 'ville_prestation' => 'Bordeaux'];
$val = static fn (array $modif, ?callable $h = null): array => OrderValidator::validate(array_merge($base, $modif), $menu, $h ?? $horaires, $maintenant)['errors'];
$erreur = static fn (array $modif, string $champ): bool => isset($val($modif)[$champ]);

verifier('Commande valide', $val([]), []);
verifier('Données normalisées', OrderValidator::validate($base, $menu, $horaires, $maintenant)['data'],
    ['menu_id' => 1, 'personnes' => 6, 'date_prestation' => '2026-11-20', 'heure_livraison' => '12:30',
     'adresse_prestation' => '12 rue Sainte-Catherine', 'code_postal_prestation' => '33000', 'ville_prestation' => 'Bordeaux']);
verifier('Date au dernier jour du délai (aujourd\'hui + 14)', $val(['date_prestation' => '2026-10-29']), []);
verifier('Date trop proche (délai de 14 jours)', $erreur(['date_prestation' => '2026-10-28'], 'date_prestation'), true);
verifier('Date passée', $erreur(['date_prestation' => '2026-10-01'], 'date_prestation'), true);
verifier('Date inexistante (30 février)', $erreur(['date_prestation' => '2027-02-30'], 'date_prestation'), true);
verifier('Date au mauvais format', $erreur(['date_prestation' => '20/11/2026'], 'date_prestation'), true);
verifier('Date vide', $erreur(['date_prestation' => ''], 'date_prestation'), true);
verifier('Date au-delà de deux ans', $erreur(['date_prestation' => '2029-01-01'], 'date_prestation'), true);
verifier('Personnes : minimum atteint', $val(['personnes' => '6']), []);
verifier('Personnes : sous le minimum', $erreur(['personnes' => '5'], 'personnes'), true);
verifier('Personnes : zéro', $erreur(['personnes' => '0'], 'personnes'), true);
verifier('Personnes : texte', $erreur(['personnes' => 'abc'], 'personnes'), true);
verifier('Personnes : décimal', $erreur(['personnes' => '6.5'], 'personnes'), true);
verifier('Personnes : au-delà du maximum', $erreur(['personnes' => '501'], 'personnes'), true);
verifier('Heure : ouverture (vendredi 09:00)', $val(['heure_livraison' => '09:00']), []);
verifier('Heure : fermeture (vendredi 19:00)', $val(['heure_livraison' => '19:00']), []);
verifier('Heure : avant l\'ouverture', $erreur(['heure_livraison' => '08:59'], 'heure_livraison'), true);
verifier('Heure : après la fermeture', $erreur(['heure_livraison' => '19:01'], 'heure_livraison'), true);
verifier('Heure : dimanche après 14:00', $erreur(['date_prestation' => '2026-11-22', 'heure_livraison' => '15:00'], 'heure_livraison'), true);
verifier('Heure : dimanche 10:00', $val(['date_prestation' => '2026-11-22', 'heure_livraison' => '10:00']), []);
verifier('Heure : format invalide', $erreur(['heure_livraison' => '25:00'], 'heure_livraison'), true);
verifier('Jour de fermeture', isset($val([], static fn (int $j): array => ['heure_ouverture' => null, 'heure_fermeture' => null, 'ferme' => 1])['date_prestation']), true);
verifier('Adresse trop courte', $erreur(['adresse_prestation' => '12'], 'adresse_prestation'), true);
verifier('Code postal invalide', $erreur(['code_postal_prestation' => '3300'], 'code_postal_prestation'), true);
verifier('Ville invalide', $erreur(['ville_prestation' => '12345'], 'ville_prestation'), true);
verifier('Tableau à la place d\'un texte', $erreur(['ville_prestation' => ['x']], 'ville_prestation'), true);

echo "--- Horaires en base\n";
$lundi = (new HoraireRepository())->forDay(1);
verifier('Lundi : 09:00 - 19:00', [substr((string) $lundi['heure_ouverture'], 0, 5), substr((string) $lundi['heure_fermeture'], 0, 5)], ['09:00', '19:00']);

// ================= Enregistrement (base de données) =================
echo "--- Enregistrement d'une commande\n";
$pdo = Database::pdo();
$stockInitial = (int) $pdo->query('SELECT stock FROM menu WHERE id = 1')->fetchColumn();
$email = 'cmd.test@vite-gourmand.test';
$pdo->prepare("DELETE FROM utilisateur WHERE email = :e")->execute(['e' => $email]);
$pdo->prepare("INSERT INTO utilisateur (email, mot_de_passe, nom, prenom, role_id, actif)
               VALUES (:e, 'x', 'Test', 'Commande', (SELECT id FROM role WHERE libelle = 'utilisateur'), 1)")->execute(['e' => $email]);
$userId = (int) $pdo->lastInsertId();
$autreId = 0;

try {
    $service = new OrderService();
    $dans30jours = (new DateTimeImmutable('today'))->modify('+30 days')->format('Y-m-d');
    $in = ['menu_id' => 1, 'personnes' => 6, 'date_prestation' => $dans30jours, 'heure_livraison' => '12:00',
        'adresse_prestation' => '12 rue Sainte-Catherine', 'code_postal_prestation' => '33000', 'ville_prestation' => 'Bordeaux'];
    $bordeaux = ['km10' => 0, 'bordeaux' => true, 'estime' => false];
    $refuse = static function (callable $f): ?string {
        try {
            $f();
        } catch (OrderException $e) {
            return $e->raison;
        }

        return null;
    };
    $compter = static fn (string $sql, array $p = []): int => (function () use ($pdo, $sql, $p): int {
        $s = $pdo->prepare($sql);
        $s->execute($p);

        return (int) $s->fetchColumn();
    })();

    verifier('Délai du menu 1 en base : 14 jours (sql/05_seed_delais.sql appliqué)', (int) $pdo->query('SELECT delai_commande_jours FROM menu WHERE id = 1')->fetchColumn(), 14);
    $pdo->exec('UPDATE menu SET stock = 1 WHERE id = 1');
    $c = $service->place($userId, $in, $bordeaux, 15000);
    verifier('Numéro de commande au bon format', (bool) preg_match('/^VG-\d{4}-\d{6}$/', $c['numero']), true);
    verifier('Total recalculé côté serveur', $c['devis']['total'], 15000);
    $ligne = $pdo->query("SELECT * FROM commande WHERE id = {$c['id']}")->fetch();
    verifier('Statut initial « en_attente »', $ligne['statut'], 'en_attente');
    verifier('Prix enregistrés (menu, remise, livraison, total)', [$ligne['prix_menu'], $ligne['remise'], $ligne['prix_livraison'], $ligne['prix_total']],
        ['150.00', '0.00', '0.00', '150.00']);
    verifier('Preuve d\'acceptation des conditions', $ligne['conditions_acceptees_at'] !== null, true);
    verifier('Stock décrémenté', (int) $pdo->query('SELECT stock FROM menu WHERE id = 1')->fetchColumn(), 0);
    verifier('Historique : une ligne « en_attente »', $compter("SELECT COUNT(*) FROM commande_statut_historique WHERE commande_id = {$c['id']} AND statut = 'en_attente'"), 1);
    verifier('Stock épuisé : commande refusée', $refuse(fn () => $service->place($userId, $in, $bordeaux)), 'stock');
    verifier('Stock épuisé : aucune commande créée', $compter('SELECT COUNT(*) FROM commande WHERE utilisateur_id = ?', [$userId]), 1);

    $pdo->exec('UPDATE menu SET stock = 5 WHERE id = 1');
    verifier('Moins que le minimum : refusé', $refuse(fn () => $service->place($userId, ['personnes' => 5] + $in, $bordeaux)), 'minimum');
    verifier('Délai non respecté : refusé', $refuse(fn () => $service->place($userId, ['date_prestation' => (new DateTimeImmutable('today'))->modify('+3 days')->format('Y-m-d')] + $in, $bordeaux)), 'delai');
    verifier('Prix modifié depuis le récapitulatif : refusé', $refuse(fn () => $service->place($userId, $in, $bordeaux, 14999)), 'prix');
    $pdo->exec('UPDATE menu SET actif = 0 WHERE id = 1');
    verifier('Menu désactivé : refusé', $refuse(fn () => $service->place($userId, $in, $bordeaux)), 'menu');
    $pdo->exec('UPDATE menu SET actif = 1 WHERE id = 1');
    verifier('Les refus ne touchent pas au stock', (int) $pdo->query('SELECT stock FROM menu WHERE id = 1')->fetchColumn(), 5);

    $loin = ['km10' => 123, 'bordeaux' => false, 'estime' => false];
    $c2 = $service->place($userId, ['personnes' => 11] + $in, $loin, 25976);
    $l2 = $pdo->query("SELECT * FROM commande WHERE id = {$c2['id']}")->fetch();
    verifier('Hors Bordeaux, 11 personnes : montants enregistrés', [$l2['prix_menu'], $l2['remise'], $l2['prix_livraison'], $l2['prix_total'], $l2['distance_km']],
        ['247.50', '27.50', '12.26', '259.76', '12.3']);

    echo "--- Lecture d'une commande\n";
    $repo = new OrderRepository();
    verifier('Le propriétaire retrouve sa commande', $repo->findByNumero($c['numero'], $userId)['menu_titre'] ?? null, 'Menu de Noël traditionnel');
    $pdo->prepare("INSERT INTO utilisateur (email, mot_de_passe, role_id, actif)
                   VALUES ('cmd.autre@vite-gourmand.test', 'x', (SELECT id FROM role WHERE libelle = 'utilisateur'), 1)")->execute();
    $autreId = (int) $pdo->lastInsertId();
    verifier('Un autre client ne la voit pas', $repo->findByNumero($c['numero'], $autreId), null);
} finally {
    $pdo->prepare('DELETE FROM commande WHERE utilisateur_id IN (?, ?)')->execute([$userId, $autreId]); // l'historique part en cascade
    $pdo->prepare("DELETE FROM utilisateur WHERE email IN ('cmd.test@vite-gourmand.test', 'cmd.autre@vite-gourmand.test')")->execute();
    $pdo->prepare('UPDATE menu SET stock = :s, actif = 1 WHERE id = 1')->execute(['s' => $stockInitial]);
}

echo $echecs === 0 ? "\nTous les tests passent.\n" : "\n$echecs test(s) en échec.\n";
exit($echecs === 0 ? 0 : 1);
