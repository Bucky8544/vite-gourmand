<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Service\AuthService;
use App\Service\PasswordResetService;

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

$pdo = Database::pdo();
$emails = ['reset.a@vite-gourmand.test', 'reset.b@vite-gourmand.test', 'reset.inactif@vite-gourmand.test'];
$nettoyer = static function () use ($pdo, $emails): void {
    $pdo->prepare('DELETE FROM utilisateur WHERE email IN (?, ?, ?)')->execute($emails); // les jetons partent en cascade
};
$creer = static function (string $email, int $actif) use ($pdo): void {
    $pdo->prepare("INSERT INTO utilisateur (email, mot_de_passe, role_id, actif)
                   VALUES (:e, :h, (SELECT id FROM role WHERE libelle = 'utilisateur'), :a)")
        ->execute(['e' => $email, 'h' => password_hash('Ancien@Pass2026!', PASSWORD_DEFAULT), 'a' => $actif]);
};
$compter = static function (string $hash) use ($pdo): int {
    $s = $pdo->prepare('SELECT COUNT(*) FROM password_reset_token WHERE token_hash = :h');
    $s->execute(['h' => $hash]);

    return (int) $s->fetchColumn();
};

$nettoyer();
try {
    $creer($emails[0], 1);
    $creer($emails[1], 1);
    $creer($emails[2], 0);

    $mails = [];
    $changes = [];
    $svc = new PasswordResetService(
        null,
        null,
        function (string $e, ?string $p, string $url) use (&$mails): void { $mails[] = ['email' => $e, 'url' => $url]; },
        function (string $e, ?string $p) use (&$changes): void { $changes[] = $e; }
    );

    echo "--- Demande\n";
    $svc->requestReset('inconnu@exemple.fr');
    verifier('E-mail inconnu : aucun mail, aucune erreur', count($mails), 0);
    $svc->requestReset($emails[2]);
    verifier('Compte désactivé : aucun mail', count($mails), 0);
    $svc->requestReset('RESET.A@vite-gourmand.test');
    verifier('Compte actif : un mail (e-mail insensible à la casse)', count($mails), 1);

    $url1 = $mails[0]['url'];
    $t1 = basename($url1);
    $base = rtrim(getenv('APP_URL') ?: 'http://localhost:8081', '/') . '/reinitialisation/';
    verifier('Lien construit depuis APP_URL', str_starts_with($url1, $base), true);
    verifier('Jeton de 64 caractères hexadécimaux', (bool) preg_match('/^[0-9a-f]{64}$/', $t1), true);
    verifier('Le jeton en clair n\'est pas stocké', $compter($t1), 0);
    verifier('Seul son hash SHA-256 est stocké', $compter(hash('sha256', $t1)), 1);

    echo "--- Validité\n";
    verifier('Jeton valide accepté', $svc->isValid($t1), true);
    verifier('Jeton mal formé refusé', $svc->isValid('abc'), false);
    verifier('Jeton inconnu refusé', $svc->isValid(str_repeat('0', 64)), false);

    $tExp = str_repeat('a', 64);
    $pdo->prepare('INSERT INTO password_reset_token (utilisateur_id, token_hash, expires_at)
                   VALUES ((SELECT id FROM utilisateur WHERE email = :e), :h, DATE_SUB(NOW(), INTERVAL 1 MINUTE))')
        ->execute(['e' => $emails[0], 'h' => hash('sha256', $tExp)]);
    verifier('Jeton expiré refusé', $svc->isValid($tExp), false);
    verifier('Réinitialisation avec un jeton expiré refusée', $svc->reset($tExp, 'Nouveau@Pass2026'), false);

    echo "--- Réinitialisation\n";
    $svc->requestReset($emails[0]);
    $t2 = basename($mails[1]['url']);
    verifier('Deux jetons valides en parallèle', $svc->isValid($t1) && $svc->isValid($t2), true);
    verifier('Réinitialisation réussie', $svc->reset($t2, 'Nouveau@Pass2026'), true);
    verifier('Mail de confirmation envoyé', $changes, [$emails[0]]);
    verifier('Jeton utilisé refusé', $svc->isValid($t2), false);
    verifier('Réutilisation du jeton refusée', $svc->reset($t2, 'Autre@Pass2026!'), false);
    verifier('Les autres jetons du compte sont invalidés', $svc->isValid($t1), false);

    $auth = new AuthService();
    verifier('Connexion avec le nouveau mot de passe', $auth->attempt($emails[0], 'Nouveau@Pass2026')['role'] ?? null, 'utilisateur');
    verifier('Ancien mot de passe refusé', $auth->attempt($emails[0], 'Ancien@Pass2026!'), null);

    echo "--- Limitation\n";
    $avant = count($mails);
    for ($i = 0; $i < 5; $i++) {
        $svc->requestReset($emails[1]);
    }
    verifier('Au plus 3 mails par heure et par compte', count($mails) - $avant, PasswordResetService::MAX_PAR_HEURE);
} finally {
    $nettoyer();
}

echo $echecs === 0 ? "\nTous les tests passent.\n" : "\n$echecs test(s) en échec.\n";
exit($echecs === 0 ? 0 : 1);
