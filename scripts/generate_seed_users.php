<?php
declare(strict_types=1);

// Comptes de test : identifiants à reprendre dans le manuel d'utilisation.
// Mots de passe conformes à la règle du sujet (10 car. min, maj., min., chiffre, spécial).
$comptes = [
    ['role' => 'administrateur', 'email' => 'admin@vite-gourmand.test',
     'mdp' => 'Admin@Vite2026!', 'nom' => 'Admin', 'prenom' => 'José'],
    ['role' => 'employe', 'email' => 'employe@vite-gourmand.test',
     'mdp' => 'Employe@Vite2026!', 'nom' => 'Employé', 'prenom' => 'Julie'],
    ['role' => 'utilisateur', 'email' => 'utilisateur@vite-gourmand.test',
     'mdp' => 'Client@Vite2026!', 'nom' => 'Dupont', 'prenom' => 'Camille',
     'telephone' => '0612345678', 'adresse' => '12 rue Sainte-Catherine',
     'cp' => '33000', 'ville' => 'Bordeaux'],
];

function q(?string $v): string
{
    return $v === null ? 'NULL' : "'" . str_replace("'", "''", $v) . "'";
}

$sql = "SET NAMES utf8mb4;\n\n-- Fichier généré par scripts/generate_seed_users.php : ne pas modifier à la main.\n\n";

foreach ($comptes as $c) {
    $hash = password_hash($c['mdp'], PASSWORD_DEFAULT);
    $sql .= sprintf(
        "INSERT INTO utilisateur (email, mot_de_passe, nom, prenom, telephone, adresse_postale, code_postal, ville, role_id, actif, consentement_rgpd_at)\n"
        . "VALUES (%s, %s, %s, %s, %s, %s, %s, %s, (SELECT id FROM role WHERE libelle = %s), TRUE, NOW());\n\n",
        q($c['email']), q($hash), q($c['nom']), q($c['prenom']),
        q($c['telephone'] ?? null), q($c['adresse'] ?? null),
        q($c['cp'] ?? null), q($c['ville'] ?? null), q($c['role'])
    );
}

file_put_contents(__DIR__ . '/../sql/04_seed_users.sql', $sql);
echo "sql/04_seed_users.sql généré (" . count($comptes) . " comptes)\n";
