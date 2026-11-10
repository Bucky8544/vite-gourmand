<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Repository\ReviewRepository;
use App\Service\OrderException;
use App\Service\OrderService;
use App\Service\ReviewModerationService;
use App\Service\ReviewService;

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

echo "--- Nom affiché publiquement\n";
verifier('Prénom et initiale', ReviewRepository::auteur('Camille', 'Dupont'), 'Camille D.');
verifier('Initiale accentuée mise en majuscule', ReviewRepository::auteur('Élodie', 'étienne'), 'Élodie É.');
verifier('Sans nom : prénom seul', ReviewRepository::auteur('Camille', null), 'Camille');
verifier('Sans prénom : « Un client »', ReviewRepository::auteur(null, 'Dupont'), 'Un client');
verifier('Espaces ignorés', ReviewRepository::auteur('  Léa ', ' martin '), 'Léa M.');

$pdo = Database::pdo();
$stockInitial = (int) $pdo->query('SELECT stock FROM menu WHERE id = 1')->fetchColumn();
$emails = ['avis.client@vite-gourmand.test', 'avis.staff@vite-gourmand.test'];

$nettoyer = static function () use ($pdo, $emails): void {
    $pdo->prepare('DELETE FROM avis WHERE commande_id IN (SELECT id FROM commande WHERE utilisateur_id IN (SELECT id FROM utilisateur WHERE email IN (?, ?)))')->execute($emails);
    $pdo->prepare('DELETE FROM commande WHERE utilisateur_id IN (SELECT id FROM utilisateur WHERE email IN (?, ?))')->execute($emails);
    $pdo->prepare('DELETE FROM utilisateur WHERE email IN (?, ?)')->execute($emails);
};
$creer = static function (string $email, string $role) use ($pdo): int {
    $pdo->prepare("INSERT INTO utilisateur (email, mot_de_passe, nom, prenom, telephone, adresse_postale, code_postal, ville, role_id, actif)
                   VALUES (:e, 'x', 'Durand', 'Alice', '0612345678', '1 rue du Test', '33000', 'Bordeaux',
                           (SELECT id FROM role WHERE libelle = :r), 1)")->execute(['e' => $email, 'r' => $role]);

    return (int) $pdo->lastInsertId();
};

$nettoyer();
try {
    $client = $creer($emails[0], 'utilisateur');
    $staff = $creer($emails[1], 'employe');
    $pdo->exec('UPDATE menu SET stock = 20 WHERE id = 1');

    $repo = new ReviewRepository();
    $moderation = new ReviewModerationService();
    $bordeaux = ['km10' => 0, 'bordeaux' => true, 'estime' => false];
    $in = ['menu_id' => 1, 'personnes' => 6, 'date_prestation' => (new DateTimeImmutable('today'))->modify('+30 days')->format('Y-m-d'),
        'heure_livraison' => '12:00', 'adresse_prestation' => '12 rue Sainte-Catherine', 'code_postal_prestation' => '33000', 'ville_prestation' => 'Bordeaux'];
    $refuse = static function (callable $f): ?string {
        try {
            $f();
        } catch (OrderException $e) {
            return $e->raison;
        }

        return null;
    };

    $avantCompte = $repo->countByStatus();
    $avantStats = $repo->statsValidated();

    $marqueurs = ['AVIS-TEST-1 excellent', 'AVIS-TEST-2 moyen', 'AVIS-TEST-3 décevant'];
    $notes = [5, 3, 1];
    $avisIds = [];
    foreach ($marqueurs as $i => $texte) {
        $commande = (new OrderService())->place($client, $in, $bordeaux);
        $pdo->prepare("UPDATE commande SET statut = 'terminee' WHERE id = :id")->execute(['id' => $commande['id']]);
        (new ReviewService())->submit($client, $commande['numero'], $notes[$i], $texte);
        $avisIds[$i] = (int) $pdo->query('SELECT id FROM avis WHERE commande_id = ' . (int) $commande['id'])->fetchColumn();
    }
    $statutDe = static fn (int $id): string => (string) $pdo->query("SELECT statut FROM avis WHERE id = $id")->fetchColumn();
    $accueil = static fn (): array => array_column($repo->validatedForHome(24), 'commentaire');

    echo "--- Avis en attente\n";
    $compte = $repo->countByStatus();
    verifier('Trois nouveaux avis en attente', $compte['en_attente'] - $avantCompte['en_attente'], 3);
    verifier('Statut initial', array_map($statutDe, $avisIds), ['en_attente', 'en_attente', 'en_attente']);
    verifier('Aucun avis en attente sur la page d\'accueil', array_intersect($marqueurs, $accueil()), []);
    verifier('Jamais d\'avis refusé ou en attente dans les statistiques', $repo->statsValidated()['count'], $avantStats['count']);

    echo "--- Validation\n";
    $moderation->decide($staff, $avisIds[0], 'valide');
    $l = $pdo->query("SELECT statut, modere_par, modere_at FROM avis WHERE id = {$avisIds[0]}")->fetch();
    verifier('Avis validé, modérateur et date enregistrés', [$l['statut'], (int) $l['modere_par'] === $staff, $l['modere_at'] !== null], ['valide', true, true]);
    verifier('Avis validé : visible sur la page d\'accueil', in_array($marqueurs[0], $accueil(), true), true);
    verifier('Statistiques : un avis validé de plus', $repo->statsValidated()['count'], $avantStats['count'] + 1);
    $moyenne = $repo->statsValidated()['moyenne'];
    verifier('Note moyenne entre 1 et 5', $moyenne >= 1.0 && $moyenne <= 5.0, true);

    $lignes = array_values(array_filter($repo->validatedForHome(24), static fn (array $a): bool => $a['commentaire'] === $marqueurs[0]));
    verifier('Seules les données publiques sont exposées', [$lignes[0]['auteur'], $lignes[0]['note'], array_keys($lignes[0])], ['Alice D.', 5, ['auteur', 'note', 'commentaire', 'date']]);
    verifier('Le plus récent en premier', $repo->validatedForHome(24)[0]['commentaire'], $marqueurs[0]);

    echo "--- Refus et retour sur décision\n";
    $moderation->decide($staff, $avisIds[1], 'refuse');
    verifier('Avis refusé', $statutDe($avisIds[1]), 'refuse');
    verifier('Avis refusé : absent de la page d\'accueil', in_array($marqueurs[1], $accueil(), true), false);
    verifier('Même décision deux fois : refusée', $refuse(fn () => $moderation->decide($staff, $avisIds[0], 'valide')), 'statut');
    verifier('Avis déjà refusé : refusé de nouveau', $refuse(fn () => $moderation->decide($staff, $avisIds[1], 'refuse')), 'statut');
    $moderation->decide($staff, $avisIds[0], 'refuse');
    verifier('Avis retiré après validation', [$statutDe($avisIds[0]), in_array($marqueurs[0], $accueil(), true)], ['refuse', false]);
    $moderation->decide($staff, $avisIds[1], 'valide');
    verifier('Avis refusé puis publié', [$statutDe($avisIds[1]), in_array($marqueurs[1], $accueil(), true)], ['valide', true]);
    verifier('Décision inconnue refusée', $refuse(fn () => $moderation->decide($staff, $avisIds[2], 'supprime')), 'decision');
    verifier('Avis inconnu', $refuse(fn () => $moderation->decide($staff, 999999999, 'valide')), 'introuvable');
    verifier('Les refus n\'ont rien modifié', $statutDe($avisIds[2]), 'en_attente');

    echo "--- Liste de modération\n";
    $res = $repo->search('valide', 1);
    $trouve = array_values(array_filter($res['lignes'], static fn (array $r): bool => $r['commentaire'] === $marqueurs[1]));
    verifier('Avis trouvé avec sa commande, son client et son modérateur', [
        $trouve[0]['menu_titre'] ?? null, $trouve[0]['email'] ?? null, $trouve[0]['moderateur_email'] ?? null,
    ], ['Menu de Noël traditionnel', $emails[0], $emails[1]]);
    verifier('Page inexistante : liste vide, total conservé', [$repo->search('valide', 9999)['lignes'], $repo->search('valide', 9999)['total'] === $res['total']], [[], true]);
    verifier('Statut inconnu : aucun résultat', $repo->search('n_importe_quoi', 1)['total'], 0);
} finally {
    $nettoyer();
    $pdo->prepare('UPDATE menu SET stock = :s WHERE id = 1')->execute(['s' => $stockInitial]);
}

echo $echecs === 0 ? "\nTous les tests passent.\n" : "\n$echecs test(s) en échec.\n";
exit($echecs === 0 ? 0 : 1);
