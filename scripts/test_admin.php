<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Repository\EmployeeAdminRepository;
use App\Repository\UserRepository;
use App\Service\AdminStatsBuilder;
use App\Service\AuthService;
use App\Service\BarChart;
use App\Service\CatalogException;
use App\Service\EmployeeAdminService;
use App\Service\OrderService;
use App\Service\OrderStaffService;
use App\Service\StaffAccountMailer;
use App\Service\StatsSyncService;
use App\Validation\AdminStatsFilter;
use App\Validation\EmployeeValidator;

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
$refuse = static function (callable $f): ?string {
    try {
        $f();
    } catch (CatalogException $e) {
        return $e->raison;
    }

    return null;
};

// ================= Graphique =================
echo "--- Graphique en barres (SVG)\n";
$svg = BarChart::svg([
    ['label' => 'Menu A', 'valeur' => 10, 'texte' => '10'],
    ['label' => 'Menu B', 'valeur' => 5, 'texte' => '5'],
    ['label' => 'Menu C', 'valeur' => 0, 'texte' => '0'],
], 'Titre', 'Description', 'test');
verifier('Hauteur : 3 lignes', str_contains($svg, 'viewBox="0 0 640 176"'), true);
verifier('Accessible : rôle, titre et description reliés', [
    str_contains($svg, 'role="img"'),
    str_contains($svg, 'aria-labelledby="graph-test-titre graph-test-desc"'),
    str_contains($svg, '<title id="graph-test-titre">Titre</title>'),
    str_contains($svg, '<desc id="graph-test-desc">Description</desc>'),
], [true, true, true, true]);
verifier('La plus grande valeur occupe toute la barre', str_contains($svg, '<rect class="graph-barre" x="0" y="30" width="440" height="18" rx="4"/>'), true);
verifier('Une valeur moitié moindre : barre moitié moins longue', str_contains($svg, '<rect class="graph-barre" x="0" y="86" width="220" height="18" rx="4"/>'), true);
verifier('Valeur nulle : pas de barre, mais le fond et la valeur', [substr_count($svg, 'class="graph-barre"'), substr_count($svg, 'class="graph-fond"'), str_contains($svg, '>0</text>')], [2, 3, true]);
verifier('Aucun style ni script en ligne', [str_contains($svg, 'style='), str_contains($svg, '<script')], [false, false]);
verifier('Aucune donnée : aucun graphique', BarChart::svg([], 'T', 'D', 'x'), '');
$piege = BarChart::svg([['label' => '<script>alert(1)</script>', 'valeur' => 1, 'texte' => '"1"']], 'T', 'D', 'x');
verifier('Libellé piégé échappé', [str_contains($piege, '&lt;script&gt;'), str_contains($piege, '<script>')], [true, false]);
$long = BarChart::svg([['label' => str_repeat('L', 100), 'valeur' => 1, 'texte' => '1']], 'T', 'D', 'x');
verifier('Libellé trop long tronqué dans l\'étiquette', [str_contains($long, '…'), str_contains($long, '>' . str_repeat('L', 100) . '<')], [true, false]);
verifier('Identifiant nettoyé', str_contains(BarChart::svg([['label' => 'a', 'valeur' => 1, 'texte' => '1']], 'T', 'D', 'a" onload="x'), 'graph-aonloadx-titre'), true);
verifier('Toutes les valeurs à zéro : aucune barre', substr_count(BarChart::svg([['label' => 'a', 'valeur' => 0, 'texte' => '0']], 'T', 'D', 'x'), 'class="graph-barre"'), 0);
verifier('Valeur minuscule : barre visible (2 unités minimum)', str_contains(BarChart::svg([['label' => 'a', 'valeur' => 1, 'texte' => '1'], ['label' => 'b', 'valeur' => 100000, 'texte' => '100000']], 'T', 'D', 'x'), 'width="2" height="18"'), true);

// ================= Fusion des statistiques =================
echo "--- Fusion des menus et des statistiques\n";
$menus = [['id' => 1, 'titre' => 'A'], ['id' => 2, 'titre' => 'B'], ['id' => 3, 'titre' => 'C']];
$stats = [
    ['menu_id' => 2, 'menu_titre' => 'B ancien titre', 'nombre_commandes' => 3, 'chiffre_affaires_centimes' => 30000],
    ['menu_id' => 9, 'menu_titre' => 'Disparu', 'nombre_commandes' => 1, 'chiffre_affaires_centimes' => 5000],
    ['menu_id' => 1, 'menu_titre' => 'A', 'nombre_commandes' => 3, 'chiffre_affaires_centimes' => 45000],
];
$lignes = AdminStatsBuilder::merge($menus, $stats);
verifier('Tri : commandes, puis chiffre d\'affaires', array_column($lignes, 'titre'), ['A', 'B', 'Disparu (menu supprimé)', 'C']);
verifier('Le titre actuel du menu l\'emporte', $lignes[1]['titre'], 'B');
verifier('Menu sans commande : à zéro', [$lignes[3]['commandes'], $lignes[3]['ca_centimes']], [0, 0]);
verifier('Menu supprimé signalé', [$lignes[2]['supprime'], $lignes[0]['supprime']], [true, false]);
verifier('Aucune statistique : tous les menus à zéro', array_column(AdminStatsBuilder::merge($menus, []), 'commandes'), [0, 0, 0]);

// ================= Filtres =================
echo "--- Filtres du tableau de bord\n";
$f = AdminStatsFilter::parse(['menu' => '3', 'du' => '2026-01-01', 'au' => '2026-12-31']);
verifier('Filtres valides', [$f['errors'], $f['menu'], $f['du'], $f['au']], [[], 3, '2026-01-01', '2026-12-31']);
verifier('Début à minuit, fin à 23:59:59', [$f['debut']->format('Y-m-d H:i:s'), $f['fin']->format('Y-m-d H:i:s')], ['2026-01-01 00:00:00', '2026-12-31 23:59:59']);
verifier('Sans filtre', [AdminStatsFilter::parse([])['errors'], AdminStatsFilter::parse([])['debut'], AdminStatsFilter::parse([])['menu']], [[], null, null]);
verifier('Début seul', [AdminStatsFilter::parse(['du' => '2026-05-01'])['fin'], AdminStatsFilter::parse(['du' => '2026-05-01'])['errors']], [null, []]);
verifier('Même jour : accepté', AdminStatsFilter::parse(['du' => '2026-05-01', 'au' => '2026-05-01'])['errors'], []);
verifier('Fin avant le début : refusée', isset(AdminStatsFilter::parse(['du' => '2026-06-01', 'au' => '2026-05-01'])['errors']['au']), true);
verifier('Date inexistante refusée', isset(AdminStatsFilter::parse(['du' => '2026-02-30'])['errors']['du']), true);
verifier('Mauvais format refusé', isset(AdminStatsFilter::parse(['au' => '31/12/2026'])['errors']['au']), true);
verifier('Menu invalide refusé', isset(AdminStatsFilter::parse(['menu' => '1 OR 1=1'])['errors']['menu']), true);
verifier("Menu en tableau : ignoré, sans erreur", [AdminStatsFilter::parse(["menu" => ["1"]])["menu"], AdminStatsFilter::parse(["menu" => ["1"]])["errors"]], [null, []]);
verifier('Erreur : aucune borne transmise à la base', [AdminStatsFilter::parse(['du' => 'x'])['debut'], AdminStatsFilter::parse(['du' => 'x'])['fin']], [null, null]);

// ================= Création d'un employé : validation =================
echo "--- Validation d'un compte employé\n";
$ok = ['email' => 'Nouvel.Employe@Vite-Gourmand.test', 'mot_de_passe' => 'Employe@Test2026!', 'mot_de_passe_confirmation' => 'Employe@Test2026!'];
$v = EmployeeValidator::validate($ok);
verifier('Compte valide', $v['errors'], []);
verifier('E-mail mis en minuscules', $v['data']['email'], 'nouvel.employe@vite-gourmand.test');
$ko = static fn (array $m, string $c): bool => isset(EmployeeValidator::validate(array_merge($ok, $m))['errors'][$c]);
verifier('E-mail invalide', $ko(['email' => 'pas-un-email'], 'email'), true);
verifier('E-mail vide', $ko(['email' => ''], 'email'), true);
verifier('Mot de passe faible', $ko(['mot_de_passe' => 'faible', 'mot_de_passe_confirmation' => 'faible'], 'mot_de_passe'), true);
verifier('Confirmation différente', $ko(['mot_de_passe_confirmation' => 'autre'], 'mot_de_passe_confirmation'), true);
verifier('Le rôle saisi est ignoré', array_keys(EmployeeValidator::validate($ok + ['role' => 'administrateur'])['data']), ['email', 'password']);

// ================= Mail de création =================
echo "--- Mail de création de compte\n";
$envoyes = [];
(new StaffAccountMailer(function (string $to, string $nom, string $sujet, string $html, string $texte) use (&$envoyes): void {
    $envoyes[] = compact('to', 'sujet', 'html', 'texte');
}))->sendAccountCreated('nouvel.employe@vite-gourmand.test');
verifier('Un mail envoyé à l\'employé', [count($envoyes), $envoyes[0]['to']], [1, 'nouvel.employe@vite-gourmand.test']);
verifier('L\'identifiant et le lien de connexion sont indiqués', [str_contains($envoyes[0]['texte'], 'Identifiant : nouvel.employe@vite-gourmand.test'), str_contains($envoyes[0]['texte'], '/connexion')], [true, true]);
verifier('Le mot de passe n\'est JAMAIS dans le message', [str_contains($envoyes[0]['texte'], 'Employe@Test2026!'), str_contains($envoyes[0]['html'], 'Employe@Test2026!')], [false, false]);
verifier('Le message renvoie vers l\'administrateur', str_contains($envoyes[0]['texte'], "rapprochez-vous de l'administrateur"), true);

// ================= Base de données =================
echo "--- Comptes employés\n";
$pdo = Database::pdo();
$emails = ['adm.test.a@vite-gourmand.test', 'adm.test.b@vite-gourmand.test', 'adm.client@vite-gourmand.test'];
$stockInitial = (int) $pdo->query('SELECT stock FROM menu WHERE id = 1')->fetchColumn();
$collection = (new MongoDB\Client(getenv('MONGO_URI') ?: 'mongodb://mongo:27017'))->selectCollection(getenv('MONGO_DB') ?: 'vite_gourmand', 'commandes_stats_test');

$nettoyer = static function () use ($pdo, $emails): void {
    $pdo->prepare('DELETE FROM commande WHERE utilisateur_id IN (SELECT id FROM utilisateur WHERE email = ?)')->execute([$emails[2]]);
    $pdo->prepare('DELETE FROM utilisateur WHERE email IN (?, ?, ?)')->execute($emails);
};

$nettoyer();
$collection->drop();
try {
    $svc = new EmployeeAdminService();
    $repo = new EmployeeAdminRepository();
    $auth = new AuthService();
    $idAdmin = (int) $pdo->query("SELECT id FROM utilisateur WHERE email = 'admin@vite-gourmand.test'")->fetchColumn();
    $idClient = (int) $pdo->query("SELECT id FROM utilisateur WHERE email = 'utilisateur@vite-gourmand.test'")->fetchColumn();

    $id = $svc->create($emails[0], 'Employe@Test2026!');
    $role = (string) $pdo->query("SELECT r.libelle FROM utilisateur u JOIN role r ON r.id = u.role_id WHERE u.id = $id")->fetchColumn();
    $compte = $pdo->query("SELECT actif, mot_de_passe FROM utilisateur WHERE id = $id")->fetch();
    verifier('Compte créé : rôle « employe » et actif', [$role, (int) $compte['actif']], ['employe', 1]);
    verifier('Mot de passe haché, jamais en clair', [$compte['mot_de_passe'] !== 'Employe@Test2026!', str_starts_with($compte['mot_de_passe'], '$2y$'), password_verify('Employe@Test2026!', $compte['mot_de_passe'])], [true, true, true]);
    verifier('Le nouvel employé peut se connecter', $auth->attempt($emails[0], 'Employe@Test2026!')['role'] ?? null, 'employe');
    verifier('Mauvais mot de passe refusé', $auth->attempt($emails[0], 'Autre@Test2026!'), null);
    verifier('Doublon refusé', $refuse(fn () => $svc->create($emails[0], 'Employe@Test2026!')), 'doublon');
    verifier('Adresse d\'un client déjà utilisée : refusée', $refuse(fn () => $svc->create('utilisateur@vite-gourmand.test', 'Employe@Test2026!')), 'doublon');

    $liste = $repo->all();
    $adresses = array_column($liste, 'email');
    verifier('Le compte figure dans la liste des employés', in_array($emails[0], $adresses, true), true);
    verifier('La liste ne contient ni l\'administrateur ni les clients', [in_array('admin@vite-gourmand.test', $adresses, true), in_array('utilisateur@vite-gourmand.test', $adresses, true)], [false, false]);

    $svc->setActive($id, false);
    verifier('Compte désactivé : connexion refusée', $auth->attempt($emails[0], 'Employe@Test2026!'), null);
    verifier('Compte désactivé : session existante invalide', (new UserRepository())->findActiveById($id), null);
    $svc->setActive($id, true);
    verifier('Compte réactivé : connexion de nouveau possible', $auth->attempt($emails[0], 'Employe@Test2026!')['role'] ?? null, 'employe');
    verifier('Désactiver l\'administrateur : refusé', $refuse(fn () => $svc->setActive($idAdmin, false)), 'role');
    verifier('Désactiver un client : refusé', $refuse(fn () => $svc->setActive($idClient, false)), 'role');
    verifier('L\'administrateur et le client sont intacts', [(int) $pdo->query("SELECT actif FROM utilisateur WHERE id = $idAdmin")->fetchColumn(), (int) $pdo->query("SELECT actif FROM utilisateur WHERE id = $idClient")->fetchColumn()], [1, 1]);
    verifier('Compte inconnu', $refuse(fn () => $svc->setActive(999999999, false)), 'introuvable');

    echo "--- Reconstruction des statistiques MongoDB\n";
    $sync = new StatsSyncService();
    $attendu = static fn (): int => (int) $pdo->query("SELECT COUNT(*) FROM commande WHERE statut NOT IN ('en_attente', 'annulee')")->fetchColumn();
    $n0 = $sync->resync($collection);
    verifier('Toutes les commandes acceptées et au-delà sont comptabilisées', [$n0, $collection->countDocuments()], [$attendu(), $attendu()]);
    verifier('Reconstruction répétée : même résultat, pas de doublon', [$sync->resync($collection), $collection->countDocuments()], [$attendu(), $attendu()]);

    $idClientTest = (function () use ($pdo, $emails): int {
        $pdo->prepare("INSERT INTO utilisateur (email, mot_de_passe, nom, prenom, telephone, adresse_postale, code_postal, ville, role_id, actif)
                       VALUES (:e, 'x', 'Test', 'Admin', '0612345678', '1 rue du Test', '33000', 'Bordeaux', (SELECT id FROM role WHERE libelle = 'utilisateur'), 1)")->execute(['e' => $emails[2]]);

        return (int) $pdo->lastInsertId();
    })();
    $pdo->exec('UPDATE menu SET stock = 20 WHERE id = 1');
    $commande = (new OrderService())->place($idClientTest, ['menu_id' => 1, 'personnes' => 6,
        'date_prestation' => (new DateTimeImmutable('today'))->modify('+30 days')->format('Y-m-d'), 'heure_livraison' => '12:00',
        'adresse_prestation' => '12 rue Sainte-Catherine', 'code_postal_prestation' => '33000', 'ville_prestation' => 'Bordeaux'],
        ['km10' => 0, 'bordeaux' => true, 'estime' => false]);
    $presente = static fn (): int => $collection->countDocuments(['commande_id' => $commande['id']]);

    $sync->resync($collection);
    verifier('Commande en attente : pas comptabilisée', $presente(), 0);
    (new OrderStaffService())->changeStatus($id, $commande['numero'], 'accepte');
    $sync->resync($collection);
    $doc = $collection->findOne(['commande_id' => $commande['id']], ['typeMap' => ['root' => 'array', 'document' => 'array']]);
    verifier('Commande acceptée : comptabilisée avec son montant', [$presente(), $doc['montant_centimes'] ?? null, $doc['menu_id'] ?? null], [1, 15000, 1]);
    (new OrderStaffService())->cancel($id, $commande['numero'], 'gsm', 'Annulation pour le test');
    $sync->resync($collection);
    verifier('Commande annulée : plus comptabilisée', $presente(), 0);
    verifier('Le total correspond à la base', $collection->countDocuments(), $attendu());
} finally {
    $nettoyer();
    $pdo->prepare('UPDATE menu SET stock = :s WHERE id = 1')->execute(['s' => $stockInitial]);
    $collection->drop();
}

echo $echecs === 0 ? "\nTous les tests passent.\n" : "\n$echecs test(s) en échec.\n";
exit($echecs === 0 ? 0 : 1);
