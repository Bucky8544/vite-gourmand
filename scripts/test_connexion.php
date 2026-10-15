<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Core\Http;
use App\Service\AuthService;

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

$auth = new AuthService();

echo "--- Comptes de test\n";
$u = $auth->attempt('admin@vite-gourmand.test', 'Admin@Vite2026!');
verifier('Administrateur : connexion réussie', $u['role'] ?? null, 'administrateur');
verifier('Le hash du mot de passe n\'est pas renvoyé', isset($u['mot_de_passe']), false);
verifier('Employé : connexion réussie', $auth->attempt('employe@vite-gourmand.test', 'Employe@Vite2026!')['role'] ?? null, 'employe');
verifier('Utilisateur : connexion réussie', $auth->attempt('utilisateur@vite-gourmand.test', 'Client@Vite2026!')['role'] ?? null, 'utilisateur');
verifier('E-mail insensible à la casse', $auth->attempt('ADMIN@Vite-Gourmand.test', 'Admin@Vite2026!')['role'] ?? null, 'administrateur');

echo "--- Refus\n";
verifier('Mauvais mot de passe', $auth->attempt('admin@vite-gourmand.test', 'mauvais'), null);
verifier('E-mail inconnu', $auth->attempt('inconnu@exemple.fr', 'Admin@Vite2026!'), null);
verifier('Mot de passe vide', $auth->attempt('admin@vite-gourmand.test', ''), null);

echo "--- Compte désactivé (transaction annulée à la fin)\n";
$pdo = Database::pdo();
$pdo->beginTransaction();
try {
    $pdo->prepare("INSERT INTO utilisateur (email, mot_de_passe, role_id, actif)
                   VALUES (:e, :h, (SELECT id FROM role WHERE libelle = 'employe'), 0)")
        ->execute(['e' => 'ancien.employe@vite-gourmand.test', 'h' => password_hash('Ancien@Vite2026!', PASSWORD_DEFAULT)]);
    verifier('Compte désactivé refusé avec le bon mot de passe',
        $auth->attempt('ancien.employe@vite-gourmand.test', 'Ancien@Vite2026!'), null);

    $pdo->prepare("UPDATE utilisateur SET actif = 1 WHERE email = :e")->execute(['e' => 'ancien.employe@vite-gourmand.test']);
    verifier('Le même compte réactivé est accepté',
        $auth->attempt('ancien.employe@vite-gourmand.test', 'Ancien@Vite2026!')['role'] ?? null, 'employe');
} finally {
    $pdo->rollBack();
}

echo "--- Redirections sûres\n";
foreach ([
    ['/admin', true], ['/connexion?redirect=%2Fadmin', true],
    ['//evil.com', false], ['/\\evil.com', false], ['http://evil.com', false],
    ['', false], ["/ok\r\nSet-Cookie: x=1", false], ['admin', false],
] as [$chemin, $attendu]) {
    verifier('« ' . addcslashes($chemin, "\r\n") . ' »', Http::isSafePath($chemin), $attendu);
}

echo $echecs === 0 ? "\nTous les tests passent.\n" : "\n$echecs test(s) en échec.\n";
exit($echecs === 0 ? 0 : 1);
