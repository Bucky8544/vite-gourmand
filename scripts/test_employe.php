<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Repository\StaffOrderRepository;
use App\Service\OrderException;
use App\Service\OrderService;
use App\Service\OrderStaffEffects;
use App\Service\OrderStaffService;
use App\Service\OrderStatus;
use App\Service\StaffMailer;
use App\Service\StatsRepository;
use App\Validation\CancelValidator;

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

// ================= Règles de statuts =================
echo "--- Machine à états\n";
verifier('Après « en attente »', OrderStatus::suivants('en_attente'), ['accepte']);
verifier('Après « acceptée »', OrderStatus::suivants('accepte'), ['en_preparation']);
verifier('Après « livrée » sans matériel', OrderStatus::suivants('livre', false), ['terminee']);
verifier('Après « livrée » avec matériel', OrderStatus::suivants('livre', true), ['attente_retour_materiel']);
verifier('Après « attente du retour »', OrderStatus::suivants('attente_retour_materiel'), ['terminee']);
verifier('Aucune suite après « terminée » ni « annulée »', [OrderStatus::suivants('terminee'), OrderStatus::suivants('annulee')], [[], []]);
verifier('Annulable avant la livraison seulement',
    array_map([OrderStatus::class, 'annulable'], ['en_attente', 'accepte', 'en_preparation', 'en_cours_livraison', 'livre', 'terminee', 'annulee']),
    [true, true, true, true, false, false, false]);
verifier('Statuts finaux', [OrderStatus::estFinal('terminee'), OrderStatus::estFinal('annulee'), OrderStatus::estFinal('accepte')], [true, true, false]);

echo "--- Validation d'une annulation\n";
$ok = ['mode_contact' => 'gsm', 'motif' => 'Le client a changé de projet', 'contacte' => '1'];
verifier('Annulation valide', CancelValidator::validate($ok)['errors'], []);
$ko = static fn (array $m, string $c): bool => isset(CancelValidator::validate(array_merge($ok, $m))['errors'][$c]);
verifier('Mode de contact absent refusé', $ko(['mode_contact' => ''], 'mode_contact'), true);
verifier('Mode de contact inconnu refusé', $ko(['mode_contact' => 'sms'], 'mode_contact'), true);
verifier('Motif trop court refusé', $ko(['motif' => 'abc'], 'motif'), true);
verifier('Motif de 501 caractères refusé', $ko(['motif' => str_repeat('a', 501)], 'motif'), true);
verifier('Confirmation de contact obligatoire', $ko(['contacte' => ''], 'contacte'), true);

// ================= Base de données =================
echo "--- Changements de statut\n";
$pdo = Database::pdo();
$stockInitial = (int) $pdo->query('SELECT stock FROM menu WHERE id = 1')->fetchColumn();
$emails = ['emp.client@vite-gourmand.test', 'emp.staff@vite-gourmand.test'];
$collectionTest = (new MongoDB\Client(getenv('MONGO_URI') ?: 'mongodb://mongo:27017'))
    ->selectCollection(getenv('MONGO_DB') ?: 'vite_gourmand', 'commandes_stats_test');

$nettoyer = static function () use ($pdo, $emails): void {
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

    $service = new OrderService();
    $equipe = new OrderStaffService();
    $repo = new StaffOrderRepository();
    $bordeaux = ['km10' => 0, 'bordeaux' => true, 'estime' => false];
    $in = ['menu_id' => 1, 'personnes' => 6, 'date_prestation' => (new DateTimeImmutable('today'))->modify('+30 days')->format('Y-m-d'),
        'heure_livraison' => '12:00', 'adresse_prestation' => '12 rue Sainte-Catherine', 'code_postal_prestation' => '33000', 'ville_prestation' => 'Bordeaux'];
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
    $statuts = static fn (int $id): array => array_column($repo->history($id), 'statut');

    $c1 = $service->place($client, $in, $bordeaux);
    verifier('Saut d\'étape refusé (en attente → en préparation)', $refuse(fn () => $equipe->changeStatus($staff, $c1['numero'], 'en_preparation')), 'transition');
    verifier('Retour en arrière refusé', $refuse(fn () => $equipe->changeStatus($staff, $c1['numero'], 'en_attente')), 'transition');
    verifier('Statut inconnu refusé', $refuse(fn () => $equipe->changeStatus($staff, $c1['numero'], 'n_importe_quoi')), 'transition');
    verifier('Commande inconnue', $refuse(fn () => $equipe->changeStatus($staff, 'VG-2026-999999', 'accepte')), 'introuvable');

    $equipe->changeStatus($staff, $c1['numero'], 'accepte');
    verifier('Acceptée', $ligne($c1['id'])['statut'], 'accepte');
    verifier('Double clic : la seconde acceptation est refusée', $refuse(fn () => $equipe->changeStatus($staff, $c1['numero'], 'accepte')), 'transition');
    $equipe->changeStatus($staff, $c1['numero'], 'en_preparation');
    $equipe->changeStatus($staff, $c1['numero'], 'en_cours_livraison');
    $equipe->changeStatus($staff, $c1['numero'], 'livre', true); // matériel prêté
    $l = $ligne($c1['id']);
    verifier('Livrée avec matériel prêté', [$l['statut'], (int) $l['pret_materiel'], (int) $l['materiel_restitue']], ['livre', 1, 0]);
    verifier('Avec matériel : « terminée » refusée', $refuse(fn () => $equipe->changeStatus($staff, $c1['numero'], 'terminee')), 'transition');
    verifier('Livrée : annulation refusée', $refuse(fn () => $equipe->cancel($staff, $c1['numero'], 'gsm', 'Trop tard pour annuler')), 'transition');
    $equipe->changeStatus($staff, $c1['numero'], 'attente_retour_materiel');
    $equipe->changeStatus($staff, $c1['numero'], 'terminee');
    $l = $ligne($c1['id']);
    verifier('Matériel restitué à la clôture', [$l['statut'], (int) $l['materiel_restitue']], ['terminee', 1]);
    verifier('Terminée : plus aucun changement', $refuse(fn () => $equipe->changeStatus($staff, $c1['numero'], 'accepte')), 'transition');
    verifier('Terminée : plus d\'annulation', $refuse(fn () => $equipe->cancel($staff, $c1['numero'], 'mail', 'Trop tard pour annuler')), 'transition');
    verifier('Historique complet dans l\'ordre', $statuts($c1['id']),
        ['en_attente', 'accepte', 'en_preparation', 'en_cours_livraison', 'livre', 'attente_retour_materiel', 'terminee']);
    $acteurs = array_column($repo->history($c1['id']), 'email');
    verifier('Auteur des changements enregistré', [$acteurs[0], $acteurs[1], $acteurs[6]], [null, $emails[1], $emails[1]]);

    $c2 = $service->place($client, $in, $bordeaux);
    foreach (['accepte', 'en_preparation', 'en_cours_livraison'] as $s) {
        $equipe->changeStatus($staff, $c2['numero'], $s);
    }
    $equipe->changeStatus($staff, $c2['numero'], 'livre', false); // pas de matériel
    verifier('Sans matériel : « attente du retour » refusée', $refuse(fn () => $equipe->changeStatus($staff, $c2['numero'], 'attente_retour_materiel')), 'transition');
    $equipe->changeStatus($staff, $c2['numero'], 'terminee');
    verifier('Sans matériel : terminée directement', $ligne($c2['id'])['statut'], 'terminee');

    echo "--- Annulation par l'équipe\n";
    $c3 = $service->place($client, $in, $bordeaux);
    $equipe->changeStatus($staff, $c3['numero'], 'accepte');
    $stockAvant = $stock();
    verifier('Mode de contact invalide refusé', $refuse(fn () => $equipe->cancel($staff, $c3['numero'], 'sms', 'Le client ne vient plus')), 'contact');
    verifier('Motif trop court refusé', $refuse(fn () => $equipe->cancel($staff, $c3['numero'], 'gsm', 'non')), 'motif');
    verifier('Les refus ne modifient rien', [$ligne($c3['id'])['statut'], $stock()], ['accepte', $stockAvant]);
    $equipe->cancel($staff, $c3['numero'], 'gsm', 'Le client a changé de projet');
    $l = $ligne($c3['id']);
    verifier('Annulée avec mode de contact et motif', [$l['statut'], $l['mode_contact_annulation'], $l['motif_annulation']], ['annulee', 'gsm', 'Le client a changé de projet']);
    verifier('Stock rendu au menu', $stock(), $stockAvant + 1);
    verifier('Historique : annulation enregistrée par l\'employé', [$statuts($c3['id']), array_column($repo->history($c3['id']), 'email')[2]],
        [['en_attente', 'accepte', 'annulee'], $emails[1]]);
    verifier('Seconde annulation refusée', $refuse(fn () => $equipe->cancel($staff, $c3['numero'], 'gsm', 'Une deuxième fois')), 'transition');

    echo "--- Recherche et filtres\n";
    $c4 = $service->place($client, $in, $bordeaux); // reste « en attente »
    $tous = $repo->search(['q' => 'emp.client'], 1);
    verifier('Recherche par e-mail : 4 commandes', $tous['total'], 4);
    verifier('Actives avant les terminées et annulées', array_column($tous['lignes'], 'statut'), ['en_attente', 'terminee', 'terminee', 'annulee']);
    verifier('Filtre par statut', $repo->search(['q' => 'emp.client', 'statut' => 'terminee'], 1)['total'], 2);
    verifier('Filtre statut sans résultat', $repo->search(['q' => 'emp.client', 'statut' => 'livre'], 1)['total'], 0);
    verifier('Recherche par nom', $repo->search(['q' => 'Durand', 'statut' => 'en_attente'], 1)['total'] >= 1, true);
    verifier('Recherche par prénom et nom', $repo->search(['q' => 'Alice Durand', 'statut' => 'annulee'], 1)['total'] >= 1, true);
    verifier('Recherche par numéro', array_column($repo->search(['q' => $c4['numero']], 1)['lignes'], 'numero_commande'), [$c4['numero']]);
    verifier('Les jokers saisis sont du texte (« % » ne trouve rien)', $repo->search(['q' => '%'], 1)['total'], 0);
    verifier('Page inexistante : liste vide, total conservé', [$repo->search(['q' => 'emp.client'], 2)['lignes'], $repo->search(['q' => 'emp.client'], 2)['total']], [[], 4]);
    verifier('Fiche : coordonnées du client', [$repo->find($c4['numero'])['email'], $repo->find($c4['numero'])['telephone']], [$emails[0], '0612345678']);
    verifier('Fiche inconnue', $repo->find('VG-2026-999999'), null);
    verifier('Synthèse par statut', ($repo->countsByStatus()['terminee'] ?? 0) >= 2, true);

    echo "--- Effets : statistiques MongoDB et mails\n";
    $collectionTest->drop();
    $envoyes = [];
    $mailer = new StaffMailer(function (string $to, string $nom, string $sujet, string $html, string $texte) use (&$envoyes): void {
        $envoyes[] = ['to' => $to, 'sujet' => $sujet, 'texte' => $texte];
    });
    $effets = new OrderStaffEffects(new StatsRepository($collectionTest), $mailer);
    $fiche = static fn (array $c): array => $repo->find($c['numero']);

    $effets->apply($fiche($c4), 'accepte');
    $doc = $collectionTest->findOne(['commande_id' => $c4['id']], ['typeMap' => ['root' => 'array', 'document' => 'array']]);
    verifier('Acceptée : document de statistiques créé', [$doc['menu_id'] ?? null, $doc['montant_centimes'] ?? null, $doc['nombre_personne'] ?? null], [1, 15000, 6]);
    verifier('Acceptée : aucun mail', $envoyes, []);
    $effets->apply($fiche($c4), 'accepte');
    verifier('Acceptée deux fois : pas de doublon', $collectionTest->countDocuments(['commande_id' => $c4['id']]), 1);

    $effets->apply($fiche($c1), 'terminee');
    verifier('Terminée : mail d\'invitation à donner son avis', [count($envoyes), $envoyes[0]['to'], str_contains($envoyes[0]['texte'], '/mon-espace/commandes/' . $c1['numero'])], [1, $emails[0], true]);
    $effets->apply($fiche($c1), 'attente_retour_materiel');
    verifier('Attente du retour : mail avec les 600 €', [count($envoyes), str_contains($envoyes[1]['texte'], '600 €'), str_contains($envoyes[1]['texte'], '10 jours ouvrés')], [2, true, true]);
    $effets->apply($fiche($c3), 'annulee');
    verifier('Annulée : mail avec le motif et le mode de contact', [count($envoyes), str_contains($envoyes[2]['texte'], 'Le client a changé de projet'), str_contains($envoyes[2]['texte'], 'par téléphone')], [3, true, true]);
    verifier('Annulée : statistiques supprimées (rien à supprimer, sans erreur)', $collectionTest->countDocuments(['commande_id' => $c3['id']]), 0);
    $effets->apply($fiche($c4), 'annulee');
    verifier('Annulée : document de statistiques retiré', $collectionTest->countDocuments(['commande_id' => $c4['id']]), 0);
    $effets->apply($fiche($c4), 'en_preparation');
    verifier('Autres statuts : ni mail ni statistique', [count($envoyes), $collectionTest->countDocuments([])], [4, 0]);

    $panneMongo = new StatsRepository((new MongoDB\Client('mongodb://127.0.0.1:1/?serverSelectionTimeoutMS=300&connectTimeoutMS=300'))->selectCollection('x', 'y'));
    $mailCasse = new StaffMailer(static function (): void {
        throw new RuntimeException('SMTP indisponible');
    });
    $panne = false;
    try {
        (new OrderStaffEffects($panneMongo, $mailCasse))->apply($fiche($c1), 'accepte');
        (new OrderStaffEffects($panneMongo, $mailCasse))->apply($fiche($c1), 'terminee');
    } catch (Throwable $e) {
        $panne = true;
    }
    verifier('MongoDB et mails en panne : aucune exception', $panne, false);
} finally {
    $nettoyer();
    $pdo->prepare('UPDATE menu SET stock = :s WHERE id = 1')->execute(['s' => $stockInitial]);
    $collectionTest->drop();
}

echo $echecs === 0 ? "\nTous les tests passent.\n" : "\n$echecs test(s) en échec.\n";
exit($echecs === 0 ? 0 : 1);
