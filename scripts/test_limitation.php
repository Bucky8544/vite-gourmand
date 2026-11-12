<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\ClientIp;
use App\Core\Database;
use App\Service\LoginThrottle;
use App\Service\RateLimiter;
use App\Service\SignupThrottle;

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

echo "--- Adresse du visiteur\n";
verifier('Sans proxy : REMOTE_ADDR', ClientIp::detect(['REMOTE_ADDR' => '203.0.113.5'], false), '203.0.113.5');
verifier('Sans proxy : X-Forwarded-For ignoré (falsifiable)', ClientIp::detect(['REMOTE_ADDR' => '203.0.113.5', 'HTTP_X_FORWARDED_FOR' => '9.9.9.9'], false), '203.0.113.5');
verifier('Derrière Heroku : dernière adresse de la liste', ClientIp::detect(['REMOTE_ADDR' => '10.1.2.3', 'HTTP_X_FORWARDED_FOR' => '9.9.9.9, 198.51.100.7'], true), '198.51.100.7');
verifier('Valeurs inventées en tête ignorées', ClientIp::detect(['REMOTE_ADDR' => '10.1.2.3', 'HTTP_X_FORWARDED_FOR' => '1.1.1.1, 2.2.2.2, 198.51.100.7'], true), '198.51.100.7');
verifier('X-Forwarded-For invalide : repli sur REMOTE_ADDR', ClientIp::detect(['REMOTE_ADDR' => '10.1.2.3', 'HTTP_X_FORWARDED_FOR' => 'pas-une-ip'], true), '10.1.2.3');
verifier('Aucune adresse : valeur neutre', ClientIp::detect([], true), '0.0.0.0');
verifier('Adresse invalide refusée', ClientIp::detect(['REMOTE_ADDR' => '<script>'], false), '0.0.0.0');
verifier('IPv6 acceptée', ClientIp::detect(['REMOTE_ADDR' => '2001:db8::1'], false), '2001:db8::1');
verifier('Regroupement : IPv4 telle quelle', ClientIp::groupe('203.0.113.5'), '203.0.113.5');
verifier('Regroupement : même préfixe /64, même groupe', ClientIp::groupe('2001:db8:0:1::1') === ClientIp::groupe('2001:db8:0:1:ffff::2'), true);
verifier('Regroupement : préfixes différents, groupes différents', ClientIp::groupe('2001:db8:0:1::1') !== ClientIp::groupe('2001:db8:0:2::1'), true);
verifier('Regroupement : IPv4 écrite en IPv6 redevient IPv4', ClientIp::groupe('::ffff:203.0.113.5'), '203.0.113.5');

echo "--- Clés (empreintes)\n";
verifier('64 caractères hexadécimaux', (bool) preg_match('/^[a-f0-9]{64}$/', LoginThrottle::cleCouple('a@b.fr', '203.0.113.5')), true);
verifier('L\'e-mail n\'apparaît pas en clair', str_contains(LoginThrottle::cleCouple('secret@exemple.fr', '203.0.113.5'), 'secret'), false);
verifier('Majuscules et espaces : même clé', LoginThrottle::cleCouple(' A@B.fr ', '203.0.113.5') === LoginThrottle::cleCouple('a@b.fr', '203.0.113.5'), true);
verifier('Autre adresse : autre clé', LoginThrottle::cleCouple('a@b.fr', '203.0.113.5') !== LoginThrottle::cleCouple('a@b.fr', '203.0.113.6'), true);
verifier('Autre e-mail : autre clé', LoginThrottle::cleCouple('a@b.fr', '203.0.113.5') !== LoginThrottle::cleCouple('c@b.fr', '203.0.113.5'), true);

echo "--- Règles de décision\n";
$now = new DateTimeImmutable('2026-11-12 12:00:00', new DateTimeZone('UTC'));
$etat = static fn (int $n, ?int $age = null): array => ['nombre' => $n, 'plus_ancien' => $age !== null ? $now->modify("-$age seconds") : null];
verifier('4 échecs : autorisé', LoginThrottle::decision($etat(4, 100), $etat(4, 100), $now), null);
verifier('5 échecs : bloqué pour le temps restant', LoginThrottle::decision($etat(5, 100), $etat(5, 100), $now), 800);
verifier('Attente minimale : 1 seconde', LoginThrottle::decision($etat(5, 5000), $etat(0), $now), 1);
verifier('20 échecs depuis l\'adresse : bloqué', LoginThrottle::decision($etat(0), $etat(20, 300), $now), 600);
verifier('Deux seuils atteints : on attend le plus long', LoginThrottle::decision($etat(5, 800), $etat(20, 100), $now), 800);
verifier('Inscriptions : la 10e déclenche le blocage', [SignupThrottle::decision($etat(9, 10), $now), SignupThrottle::decision($etat(10, 600), $now)], [null, 3000]);

echo "--- Compteur en base\n";
$pdo = Database::pdo();
$limiteur = new RateLimiter($pdo);
$t0 = new DateTimeImmutable('now', new DateTimeZone('UTC'));
$aNettoyer = [];
$adresses = ['203.0.113.77', '203.0.113.78', '203.0.113.79', '203.0.113.90', '203.0.113.91'];

try {
    $pdo->query('SELECT 1 FROM limitation_acces LIMIT 1');
    verifier('La table limitation_acces existe', true, true);

    $cle = RateLimiter::cle('test', 'compteur-unique');
    $aNettoyer[] = ['test_limitation', $cle];
    $e = $limiteur->etat('test_limitation', $cle, 900, $t0);
    verifier('Aucun événement au départ', [$e['nombre'], $e['plus_ancien']], [0, null]);

    $limiteur->enregistrer('test_limitation', $cle, $t0->modify('-20 minutes'));
    $limiteur->enregistrer('test_limitation', $cle, $t0->modify('-10 minutes'));
    $limiteur->enregistrer('test_limitation', $cle, $t0);
    $e = $limiteur->etat('test_limitation', $cle, 900, $t0);
    verifier('Fenêtre de 15 min : 2 événements (le plus vieux est hors fenêtre)', $e['nombre'], 2);
    verifier('Le plus ancien de la fenêtre', $e['plus_ancien']?->getTimestamp(), $t0->modify('-10 minutes')->getTimestamp());
    verifier('Fenêtre de 1 h : 3 événements', $limiteur->etat('test_limitation', $cle, 3600, $t0)['nombre'], 3);
    verifier('Une autre clé n\'est pas touchée', $limiteur->etat('test_limitation', RateLimiter::cle('test', 'autre'), 3600, $t0)['nombre'], 0);
    $limiteur->oublier('test_limitation', $cle);
    verifier('Après « oublier » : plus aucun événement', $limiteur->etat('test_limitation', $cle, 3600, $t0)['nombre'], 0);

    $limiteur->enregistrer('test_limitation', $cle, $t0->modify('-2 days'));
    $limiteur->enregistrer('test_limitation', $cle, $t0);
    $limiteur->purger($t0);
    verifier('Purge : l\'événement de plus de 24 h disparaît, le récent reste', $limiteur->etat('test_limitation', $cle, 7 * 86400, $t0)['nombre'], 1);

    echo "--- Limitation des connexions\n";
    $login = new LoginThrottle($limiteur);
    [$ip, $autreIp, $ipAncienne] = ['203.0.113.77', '203.0.113.78', '203.0.113.79'];
    $email = 'test-limitation@exemple.test';
    $aNettoyer[] = ['connexion_couple', LoginThrottle::cleCouple($email, $ip)];

    verifier('Aucun échec : autorisé', $login->attente($email, $ip, $t0), null);
    for ($i = 1; $i <= 4; $i++) {
        $login->echec($email, $ip, $t0);
    }
    verifier('4 échecs : toujours autorisé', $login->attente($email, $ip, $t0), null);
    $login->echec($email, $ip, $t0);
    verifier('5 échecs : bloqué pour 15 minutes', $login->attente($email, $ip, $t0), 900);
    verifier('Le même e-mail depuis une autre adresse : autorisé', $login->attente($email, $autreIp, $t0), null);
    verifier('Un autre e-mail depuis la même adresse : autorisé', $login->attente('autre-test@exemple.test', $ip, $t0), null);
    verifier('Majuscules et espaces dans l\'e-mail : même blocage', $login->attente(' TEST-Limitation@exemple.test ', $ip, $t0), 900);
    $login->succes($email, $ip);
    verifier('Après une connexion réussie : échecs du couple effacés', $login->attente($email, $ip, $t0), null);
    verifier('…mais le compteur de l\'adresse est conservé (5)', $limiteur->etat('connexion_adresse', LoginThrottle::cleAdresse($ip), 900, $t0)['nombre'], 5);

    for ($i = 1; $i <= 15; $i++) {
        $autre = "test-limitation-$i@exemple.test";
        $aNettoyer[] = ['connexion_couple', LoginThrottle::cleCouple($autre, $ip)];
        $login->echec($autre, $ip, $t0);
    }
    verifier('20 échecs depuis l\'adresse : même un nouvel e-mail est bloqué', $login->attente('nouveau@exemple.test', $ip, $t0), 900);
    verifier('Une autre adresse n\'est pas touchée', $login->attente('nouveau@exemple.test', $autreIp, $t0), null);

    $aNettoyer[] = ['connexion_couple', LoginThrottle::cleCouple($email, $ipAncienne)];
    for ($i = 1; $i <= 25; $i++) {
        $login->echec($email, $ipAncienne, $t0->modify('-20 minutes'));
    }
    verifier('Échecs de plus de 15 minutes : non comptés', $login->attente($email, $ipAncienne, $t0), null);

    echo "--- Limitation des inscriptions\n";
    $inscriptions = new SignupThrottle($limiteur);
    $ipInscription = '203.0.113.90';
    for ($i = 1; $i <= 9; $i++) {
        $inscriptions->enregistrer($ipInscription, $t0);
    }
    verifier('9 inscriptions : autorisé', $inscriptions->attente($ipInscription, $t0), null);
    $inscriptions->enregistrer($ipInscription, $t0);
    verifier('10 inscriptions : bloqué pendant 1 heure', $inscriptions->attente($ipInscription, $t0), 3600);
    verifier('Une autre adresse n\'est pas touchée', $inscriptions->attente('203.0.113.91', $t0), null);
} finally {
    foreach ($aNettoyer as [$type, $cle]) {
        $limiteur->oublier($type, $cle);
    }
    foreach ($adresses as $a) {
        $limiteur->oublier('connexion_adresse', LoginThrottle::cleAdresse($a));
        $limiteur->oublier('inscription', SignupThrottle::cle($a));
    }
}

echo $echecs === 0 ? "\nTous les tests passent.\n" : "\n$echecs test(s) en échec.\n";
exit($echecs === 0 ? 0 : 1);
