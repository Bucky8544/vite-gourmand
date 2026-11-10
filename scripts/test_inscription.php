<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Validation\PasswordPolicy;
use App\Validation\RegistrationValidator;

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

echo "--- Politique de mot de passe\n";
$cas = [
    ['Abcdef123!x', true], ['Mot de passe 1!', true],
    ['abcdef123!x', false],            // pas de majuscule
    ['ABCDEF123!X', false],            // pas de minuscule
    ['Abcdefghij!', false],            // pas de chiffre
    ['Abcdef12345', false],            // pas de caractère spécial
    ['Ab1!', false],                   // trop court
    ['Éclair2026é', false],            // les accents ne sont pas des caractères spéciaux
    [str_repeat('Aa1!', 19), false],   // plus de 72 octets
];
foreach ($cas as [$mdp, $attendu]) {
    verifier("« $mdp »", PasswordPolicy::check($mdp) === [], $attendu);
}

echo "--- Formulaire\n";
$ok = [
    'nom' => 'Dupont', 'prenom' => 'Camille', 'telephone' => '+33 6 12 34 56 78', 'email' => 'Camille@Exemple.fr',
    'adresse_postale' => '12 rue Sainte-Catherine', 'code_postal' => '33000', 'ville' => 'Bordeaux',
    'mot_de_passe' => 'Abcdef123!x', 'mot_de_passe_confirmation' => 'Abcdef123!x', 'consentement' => '1',
];
$r = RegistrationValidator::validate($ok);
verifier('Formulaire valide', $r['errors'], []);
verifier('GSM normalisé', $r['data']['telephone'], '0612345678');
verifier('E-mail en minuscules', $r['data']['email'], 'camille@exemple.fr');

$variantes = [
    'Nom avec balise refusé' => ['nom', ['nom' => '<script>alert(1)</script>']],
    'Fixe refusé comme GSM' => ['telephone', ['telephone' => '05 56 00 00 00']],
    'E-mail invalide refusé' => ['email', ['email' => 'pas-un-email']],
    'Code postal invalide refusé' => ['code_postal', ['code_postal' => '3300']],
    'Confirmation différente refusée' => ['mot_de_passe_confirmation', ['mot_de_passe_confirmation' => 'autre']],
    'Consentement manquant refusé' => ['consentement', ['consentement' => '']],
    'Mot de passe faible refusé' => ['mot_de_passe', ['mot_de_passe' => 'faible', 'mot_de_passe_confirmation' => 'faible']],
];
foreach ($variantes as $libelle => [$champ, $modif]) {
    $res = RegistrationValidator::validate(array_merge($ok, $modif));
    verifier($libelle, isset($res['errors'][$champ]), true);
}

echo $echecs === 0 ? "\nTous les tests passent.\n" : "\n$echecs test(s) en échec.\n";
exit($echecs === 0 ? 0 : 1);
