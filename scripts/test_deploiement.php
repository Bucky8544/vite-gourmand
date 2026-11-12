<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Core\Deployment;

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
$leve = static function (callable $f): ?string {
    try {
        $f();
    } catch (RuntimeException $e) {
        return $e->getMessage();
    }

    return null;
};

echo "--- Adresse de la base (JAWSDB_URL)\n";
$c = Database::depuisUrl('mysql://utilisateur:motdepasse@hote.exemple.net:3307/ma_base');
verifier('Adresse complète décomposée', [$c['host'], $c['port'], $c['name'], $c['user'], $c['pass']], ['hote.exemple.net', '3307', 'ma_base', 'utilisateur', 'motdepasse']);
verifier('Port par défaut', Database::depuisUrl('mysql://u:p@h/db')['port'], '3306');
$c = Database::depuisUrl('mysql://u:p%40ss%3A@h/db');
verifier('Mot de passe codé en pourcentage décodé', $c['pass'], 'p@ss:');
foreach (['' => 'vide', 'postgres://u:p@h/db' => 'autre type de base', 'mysql://h/db' => 'sans utilisateur', 'mysql://u:p@h' => 'sans nom de base', 'mysql://u:p@h/' => 'nom de base vide', 'pas une adresse' => 'texte quelconque'] as $url => $nom) {
    verifier("Adresse refusée : $nom", $leve(fn () => Database::depuisUrl($url)) !== null, true);
}
verifier('Le message d\'erreur ne contient jamais le mot de passe', str_contains((string) $leve(fn () => Database::depuisUrl('postgres://u:SECRET123@h/db')), 'SECRET123'), false);
if ((string) getenv('JAWSDB_URL') === '') {
    $local = Database::config();
    verifier('Sans JAWSDB_URL : variables MYSQL_* (Docker)', [$local['host'] !== '', $local['name'] !== '', $local['user'] !== ''], [true, true, true]);
}
verifier('Connexion réelle à la base', (int) Database::pdo()->query('SELECT 1')->fetchColumn(), 1);

echo "--- HTTPS\n";
verifier('HTTPS direct', Deployment::estHttps(['HTTPS' => 'on']), true);
verifier('HTTPS « off »', Deployment::estHttps(['HTTPS' => 'off']), false);
verifier('Aucune indication : HTTP', Deployment::estHttps([]), false);
verifier('Derrière le routeur (https)', Deployment::estHttps(['HTTP_X_FORWARDED_PROTO' => 'https']), true);
verifier('Derrière le routeur (http)', Deployment::estHttps(['HTTP_X_FORWARDED_PROTO' => 'http']), false);
verifier('Liste de protocoles : le premier compte', Deployment::estHttps(['HTTP_X_FORWARDED_PROTO' => 'https,http']), true);

echo "--- Redirection vers HTTPS\n";
$http = ['REQUEST_URI' => '/menus?theme=1', 'HTTP_HOST' => 'pirate.exemple.net'];
verifier('Production en HTTP : redirigé vers l\'hôte officiel', Deployment::redirectionHttps($http, 'prod', 'https://site.exemple.fr'), 'https://site.exemple.fr/menus?theme=1');
verifier('Le Host fourni par le visiteur est ignoré', str_contains((string) Deployment::redirectionHttps($http, 'prod', 'https://site.exemple.fr'), 'pirate'), false);
verifier('Développement : jamais de redirection', Deployment::redirectionHttps($http, 'dev', 'https://site.exemple.fr'), null);
verifier('Déjà en HTTPS : pas de redirection', Deployment::redirectionHttps($http + ['HTTPS' => 'on'], 'prod', 'https://site.exemple.fr'), null);
verifier('Sans adresse officielle : pas de redirection', Deployment::redirectionHttps($http, 'prod', ''), null);
verifier('Chemin « // » neutralisé', Deployment::redirectionHttps(['REQUEST_URI' => '//pirate.exemple.net'], 'prod', 'https://site.exemple.fr'), 'https://site.exemple.fr/');
verifier('Chemin sans « / » neutralisé', Deployment::redirectionHttps(['REQUEST_URI' => 'pirate'], 'prod', 'https://site.exemple.fr'), 'https://site.exemple.fr/');

echo "--- En-têtes\n";
verifier('Production en HTTPS : HSTS', Deployment::entetes('prod', true, false), ['Strict-Transport-Security: max-age=15552000']);
verifier('Développement : aucun en-tête', Deployment::entetes('dev', true, false), []);
verifier('Production en HTTP : pas de HSTS', Deployment::entetes('prod', false, false), []);
verifier('Site de démonstration : noindex', Deployment::entetes('dev', false, true), ['X-Robots-Tag: noindex, nofollow']);
verifier('Fuseau horaire de l\'application', date_default_timezone_get(), 'Europe/Paris');

echo $echecs === 0 ? "\nTous les tests passent.\n" : "\n$echecs test(s) en échec.\n";
exit($echecs === 0 ? 0 : 1);
