<?php
declare(strict_types=1);

// Crée le compte administrateur (le seul compte qui ne peut pas être créé depuis l'application).
//   php scripts/create_admin.php admin@exemple.fr                  -> création
//   php scripts/create_admin.php admin@exemple.fr --reinitialiser  -> nouveau mot de passe de cet administrateur
// Le mot de passe est saisi de façon invisible, ou lu dans la variable d'environnement ADMIN_PASSWORD.
require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Validation\PasswordPolicy;

$reinitialiser = in_array('--reinitialiser', $argv, true);
$args = array_values(array_filter(array_slice($argv, 1), static fn (string $a): bool => !str_starts_with($a, '--')));
$email = mb_strtolower(trim($args[0] ?? (string) getenv('ADMIN_EMAIL')));

if ($email === '' || mb_strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Indiquez une adresse e-mail valide : php scripts/create_admin.php admin@exemple.fr\n");
    exit(1);
}

$pdo = Database::pdo();
$admins = $pdo->query("SELECT u.email FROM utilisateur u JOIN role r ON r.id = u.role_id WHERE r.libelle = 'administrateur'")
    ->fetchAll(PDO::FETCH_COLUMN);
$stmt = $pdo->prepare('SELECT u.id, r.libelle AS role FROM utilisateur u JOIN role r ON r.id = u.role_id WHERE u.email = :e');
$stmt->execute(['e' => $email]);
$compte = $stmt->fetch();

if ($compte && $compte['role'] !== 'administrateur') {
    fwrite(STDERR, "Cette adresse est déjà utilisée par un compte « {$compte['role']} » : un compte existant n'est jamais promu administrateur.\n");
    exit(1);
}
if ($compte && !$reinitialiser) {
    fwrite(STDERR, "Cet administrateur existe déjà. Ajoutez --reinitialiser pour changer son mot de passe.\n");
    exit(1);
}
if (!$compte && $admins !== []) {
    fwrite(STDERR, 'Un administrateur existe déjà (' . implode(', ', $admins) . "). Un seul compte administrateur est prévu.\n");
    exit(1);
}

$mdp = (string) getenv('ADMIN_PASSWORD');
if ($mdp === '') {
    if (!stream_isatty(STDIN)) {
        fwrite(STDERR, "Aucun terminal disponible : fournissez le mot de passe dans la variable ADMIN_PASSWORD.\n");
        exit(1);
    }
    $saisir = static function (string $invite): string {
        fwrite(STDOUT, $invite);
        shell_exec('stty -echo');
        $valeur = (string) fgets(STDIN);
        shell_exec('stty echo');
        fwrite(STDOUT, "\n");

        return rtrim($valeur, "\r\n");
    };
    $mdp = $saisir('Mot de passe : ');
    if ($mdp !== $saisir('Confirmez le mot de passe : ')) {
        fwrite(STDERR, "Les deux saisies sont différentes.\n");
        exit(1);
    }
}

$manque = PasswordPolicy::check($mdp);
if ($manque !== []) {
    fwrite(STDERR, 'Le mot de passe doit contenir : ' . implode(', ', $manque) . ".\n");
    exit(1);
}

$hash = password_hash($mdp, PASSWORD_DEFAULT);
if ($compte) {
    $pdo->prepare('UPDATE utilisateur SET mot_de_passe = :h, actif = 1 WHERE id = :id')->execute(['h' => $hash, 'id' => $compte['id']]);
    echo "Mot de passe de l'administrateur $email réinitialisé.\n";
} else {
    $pdo->prepare("INSERT INTO utilisateur (email, mot_de_passe, role_id, actif) VALUES (:e, :h, (SELECT id FROM role WHERE libelle = 'administrateur'), 1)")
        ->execute(['e' => $email, 'h' => $hash]);
    echo "Administrateur $email créé.\n";
}
