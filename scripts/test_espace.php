<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Repository\OrderRepository;
use App\Repository\ProfileRepository;
use App\Service\OrderEditService;
use App\Service\OrderException;
use App\Service\OrderService;
use App\Service\OrderStatus;
use App\Service\ReviewService;
use App\Validation\ProfileValidator;
use App\Validation\ReviewValidator;

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

// ================= Statuts =================
echo "--- Statuts\n";
verifier('Libellé « en_attente »', OrderStatus::label('en_attente'), 'En attente de validation');
verifier('Libellé « annulee »', OrderStatus::label('annulee'), 'Annulée');
verifier('Libellé inconnu : renvoyé tel quel', OrderStatus::label('inconnu'), 'inconnu');
verifier('Modifiable : seulement « en_attente »', array_map([OrderStatus::class, 'modifiable'], ['en_attente', 'accepte', 'terminee', 'annulee']), [true, false, false, false]);
verifier('Suivi masqué avant acceptation', OrderStatus::suiviVisible([['statut' => 'en_attente']]), false);
verifier('Suivi masqué si annulée sans acceptation', OrderStatus::suiviVisible([['statut' => 'en_attente'], ['statut' => 'annulee']]), false);
verifier('Suivi visible après acceptation', OrderStatus::suiviVisible([['statut' => 'en_attente'], ['statut' => 'accepte']]), true);

// ================= Avis =================
echo "--- Validation d'un avis\n";
$avis = static fn (array $in): array => ReviewValidator::validate($in)['errors'];
verifier('Avis valide', $avis(['note' => '5', 'commentaire' => 'Excellent !']), []);
verifier('Note 1 acceptée', $avis(['note' => '1', 'commentaire' => 'x']), []);
verifier('Note 0 refusée', isset($avis(['note' => '0', 'commentaire' => 'x'])['note']), true);
verifier('Note 6 refusée', isset($avis(['note' => '6', 'commentaire' => 'x'])['note']), true);
verifier('Note absente refusée', isset($avis(['commentaire' => 'x'])['note']), true);
verifier('Note tableau refusée', isset($avis(['note' => ['5'], 'commentaire' => 'x'])['note']), true);
verifier('Commentaire vide refusé', isset($avis(['note' => '4', 'commentaire' => '   '])['commentaire']), true);
verifier('Commentaire de 1000 caractères accepté', $avis(['note' => '4', 'commentaire' => str_repeat('a', 1000)]), []);
verifier('Commentaire de 1001 caractères refusé', isset($avis(['note' => '4', 'commentaire' => str_repeat('a', 1001)])['commentaire']), true);

// ================= Profil =================
echo "--- Validation du profil\n";
$profilOk = ['nom' => 'Dupont', 'prenom' => 'Camille', 'telephone' => '+33 6 12 34 56 78',
    'adresse_postale' => '12 rue Sainte-Catherine', 'code_postal' => '33000', 'ville' => 'Bordeaux'];
$p = ProfileValidator::validate($profilOk);
verifier('Profil valide', $p['errors'], []);
verifier('GSM normalisé', $p['data']['telephone'], '0612345678');
$pe = static fn (array $m, string $c): bool => isset(ProfileValidator::validate(array_merge($profilOk, $m))['errors'][$c]);
verifier('Nom avec chiffres refusé', $pe(['nom' => 'Dupont2'], 'nom'), true);
verifier('Prénom vide refusé', $pe(['prenom' => ''], 'prenom'), true);
verifier('Fixe refusé', $pe(['telephone' => '05 56 00 00 00'], 'telephone'), true);
verifier('Code postal invalide refusé', $pe(['code_postal' => '33'], 'code_postal'), true);
verifier('Ville invalide refusée', $pe(['ville' => '123'], 'ville'), true);
verifier('Adresse trop courte refusée', $pe(['adresse_postale' => 'ab'], 'adresse_postale'), true);

// ================= Base de données =================
echo "--- Commandes d'un client\n";
$pdo = Database::pdo();
$stockInitial = (int) $pdo->query('SELECT stock FROM menu WHERE id = 1')->fetchColumn();
$emails = ['esp.a@vite-gourmand.test', 'esp.b@vite-gourmand.test'];

$nettoyer = static function () use ($pdo, $emails): void {
    $sousRequete = 'SELECT id FROM commande WHERE utilisateur_id IN (SELECT id FROM utilisateur WHERE email IN (?, ?))';
    $pdo->prepare("DELETE FROM avis WHERE commande_id IN ($sousRequete)")->execute($emails);
    $pdo->prepare('DELETE FROM commande WHERE utilisateur_id IN (SELECT id FROM utilisateur WHERE email IN (?, ?))')->execute($emails); // l'historique part en cascade
    $pdo->prepare('DELETE FROM utilisateur WHERE email IN (?, ?)')->execute($emails);
};
$creer = static function (string $email) use ($pdo): int {
    $pdo->prepare("INSERT INTO utilisateur (email, mot_de_passe, nom, prenom, telephone, adresse_postale, code_postal, ville, role_id, actif)
                   VALUES (:e, 'x', 'Test', 'Espace', '0612345678', '1 rue du Test', '33000', 'Bordeaux',
                           (SELECT id FROM role WHERE libelle = 'utilisateur'), 1)")->execute(['e' => $email]);

    return (int) $pdo->lastInsertId();
};

$nettoyer();
try {
    $a = $creer($emails[0]);
    $b = $creer($emails[1]);
    $pdo->exec('UPDATE menu SET stock = 20 WHERE id = 1');

    $service = new OrderService();
    $edit = new OrderEditService();
    $repo = new OrderRepository();
    $bordeaux = ['km10' => 0, 'bordeaux' => true, 'estime' => false];
    $loin = ['km10' => 123, 'bordeaux' => false, 'estime' => false];
    $dans30jours = (new DateTimeImmutable('today'))->modify('+30 days')->format('Y-m-d');
    $in = ['menu_id' => 1, 'personnes' => 6, 'date_prestation' => $dans30jours, 'heure_livraison' => '12:00',
        'adresse_prestation' => '12 rue Sainte-Catherine', 'code_postal_prestation' => '33000', 'ville_prestation' => 'Bordeaux'];
    $stock = static fn (): int => (int) $pdo->query('SELECT stock FROM menu WHERE id = 1')->fetchColumn();
    $ligne = static fn (int $id): array => $pdo->query("SELECT * FROM commande WHERE id = $id")->fetch();
    $refuse = static function (callable $f): ?string {
        try {
            $f();
        } catch (OrderException $e) {
            return $e->raison;
        }

        return null;
    };
    $avancer = static function (int $id, string $statut) use ($pdo): void {
        $pdo->prepare('UPDATE commande SET statut = :s WHERE id = :id')->execute(['s' => $statut, 'id' => $id]);
        $pdo->prepare('INSERT INTO commande_statut_historique (commande_id, statut, date_modification) VALUES (:c, :s, :d)')
            ->execute(['c' => $id, 's' => $statut, 'd' => (new DateTimeImmutable('now'))->format('Y-m-d H:i:s')]);
    };

    $c1 = $service->place($a, $in, $bordeaux);
    verifier('Liste du client A : 1 commande', count($repo->listForUser($a)), 1);
    verifier('Liste du client B : aucune commande', $repo->listForUser($b), []);
    $liste = $repo->listForUser($a);
    verifier('Liste : numéro et menu', [$liste[0]['numero_commande'] === $c1['numero'], $liste[0]['menu_titre']], [true, 'Menu de Noël traditionnel']);
    verifier('Lecture par le propriétaire', $repo->findByNumero($c1['numero'], $a)['id'] ?? null, $c1['id']);
    verifier('Lecture par un autre client : introuvable', $repo->findByNumero($c1['numero'], $b), null);
    verifier('Historique initial', array_column($repo->history($c1['id']), 'statut'), ['en_attente']);

    echo "--- Modification\n";
    $stockAvant = $stock();
    $m = $edit->modify($a, $c1['numero'], ['personnes' => 11] + $in, $bordeaux, 24750);
    verifier('11 personnes : nouveau total', $m['devis']['total'], 24750);
    $l = $ligne($c1['id']);
    verifier('Montants enregistrés', [$l['nombre_personne'], $l['prix_menu'], $l['remise'], $l['prix_livraison'], $l['prix_total']], [11, '247.50', '27.50', '0.00', '247.50']);
    verifier('Le stock ne change pas', $stock(), $stockAvant);
    verifier('Aucun statut ajouté', count($repo->history($c1['id'])), 1);

    $edit->modify($a, $c1['numero'], ['personnes' => 11, 'adresse_prestation' => '5 avenue de Test', 'code_postal_prestation' => '33700', 'ville_prestation' => 'Mérignac'] + $in, $loin, 25976);
    $l = $ligne($c1['id']);
    verifier('Passage hors Bordeaux : livraison, distance, adresse', [$l['prix_livraison'], $l['distance_km'], $l['prix_total'], $l['ville_prestation']], ['12.26', '12.3', '259.76', 'Mérignac']);
    $edit->modify($a, $c1['numero'], ['heure_livraison' => '14:30'] + $in, $bordeaux);
    verifier('Heure modifiée', substr((string) $ligne($c1['id'])['heure_livraison'], 0, 5), '14:30');

    verifier('Moins que le minimum : refusé', $refuse(fn () => $edit->modify($a, $c1['numero'], ['personnes' => 5] + $in, $bordeaux)), 'minimum');
    $dans3jours = (new DateTimeImmutable('today'))->modify('+3 days')->format('Y-m-d');
    verifier('Nouvelle date dans le délai : refusée', $refuse(fn () => $edit->modify($a, $c1['numero'], ['date_prestation' => $dans3jours] + $in, $bordeaux)), 'delai');
    verifier('Date passée : refusée', $refuse(fn () => $edit->modify($a, $c1['numero'], ['date_prestation' => '2020-01-01'] + $in, $bordeaux)), 'delai');
    verifier('Prix différent de celui affiché : refusé', $refuse(fn () => $edit->modify($a, $c1['numero'], $in, $bordeaux, 14999)), 'prix');
    verifier('Commande d\'un autre client : introuvable', $refuse(fn () => $edit->modify($b, $c1['numero'], $in, $bordeaux)), 'introuvable');

    $pdo->prepare('UPDATE commande SET date_prestation = :d WHERE id = :id')->execute(['d' => $dans3jours, 'id' => $c1['id']]);
    $edit->modify($a, $c1['numero'], ['date_prestation' => $dans3jours, 'personnes' => 7] + $in, $bordeaux);
    verifier('Même date (déjà dans le délai) : modification possible', (int) $ligne($c1['id'])['nombre_personne'], 7);

    echo "--- Annulation\n";
    $stockAvant = $stock();
    verifier('Commande d\'un autre client : introuvable', $refuse(fn () => $edit->cancel($b, $c1['numero'])), 'introuvable');
    $edit->cancel($a, $c1['numero']);
    $l = $ligne($c1['id']);
    verifier('Statut « annulee » et motif renseigné', [$l['statut'], $l['motif_annulation'] !== null], ['annulee', true]);
    verifier('Stock rendu au menu', $stock(), $stockAvant + 1);
    verifier('Historique : en_attente puis annulee', array_column($repo->history($c1['id']), 'statut'), ['en_attente', 'annulee']);
    verifier('Seconde annulation refusée', $refuse(fn () => $edit->cancel($a, $c1['numero'])), 'statut');
    verifier('Modification après annulation refusée', $refuse(fn () => $edit->modify($a, $c1['numero'], $in, $bordeaux)), 'statut');

    echo "--- Commande acceptée\n";
    $c2 = $service->place($a, $in, $bordeaux);
    $avancer($c2['id'], 'accepte');
    $stockAvant = $stock();
    verifier('Modification refusée', $refuse(fn () => $edit->modify($a, $c2['numero'], ['personnes' => 8] + $in, $bordeaux)), 'statut');
    verifier('Annulation refusée', $refuse(fn () => $edit->cancel($a, $c2['numero'])), 'statut');
    verifier('Rien n\'a changé', [(int) $ligne($c2['id'])['nombre_personne'], $ligne($c2['id'])['statut'], $stock()], [6, 'accepte', $stockAvant]);
    verifier('Suivi visible', OrderStatus::suiviVisible($repo->history($c2['id'])), true);

    echo "--- Avis\n";
    $rev = new ReviewService();
    verifier('Avis avant la fin de la commande : refusé', $refuse(fn () => $rev->submit($a, $c2['numero'], 5, 'Trop tôt')), 'statut');
    $avancer($c2['id'], 'terminee');
    verifier('Avis d\'un autre client : introuvable', $refuse(fn () => $rev->submit($b, $c2['numero'], 5, 'Pas à moi')), 'introuvable');
    $rev->submit($a, $c2['numero'], 5, 'Super repas, merci !');
    $v = $repo->avis($c2['id']);
    verifier('Avis enregistré, en attente de validation', [(int) $v['note'], $v['commentaire'], $v['statut']], [5, 'Super repas, merci !', 'en_attente']);
    verifier('Second avis refusé', $refuse(fn () => $rev->submit($a, $c2['numero'], 1, 'Encore')), 'avis');
    verifier('Historique dans l\'ordre', array_column($repo->history($c2['id']), 'statut'), ['en_attente', 'accepte', 'terminee']);

    echo "--- Profil en base\n";
    $profils = new ProfileRepository();
    $profils->update($a, ['nom' => 'Martin', 'prenom' => 'Léa', 'telephone' => '0698765432', 'adresse_postale' => '8 cours Victor Hugo', 'code_postal' => '33000', 'ville' => 'Bordeaux']);
    $pr = $profils->find($a);
    verifier('Profil mis à jour', [$pr['nom'], $pr['prenom'], $pr['telephone'], $pr['adresse_postale']], ['Martin', 'Léa', '0698765432', '8 cours Victor Hugo']);
    verifier('L\'e-mail n\'est pas modifié', $pr['email'], $emails[0]);
    verifier('Le profil de l\'autre client est intact', $profils->find($b)['nom'], 'Test');
} finally {
    $nettoyer();
    $pdo->prepare('UPDATE menu SET stock = :s WHERE id = 1')->execute(['s' => $stockInitial]);
}

echo $echecs === 0 ? "\nTous les tests passent.\n" : "\n$echecs test(s) en échec.\n";
exit($echecs === 0 ? 0 : 1);
