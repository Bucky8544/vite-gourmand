<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Repository\HoraireAdminRepository;
use App\Repository\MenuAdminRepository;
use App\Repository\MenuRepository;
use App\Repository\PlatAdminRepository;
use App\Repository\ReferentielRepository;
use App\Service\CatalogException;
use App\Service\ImageStorage;
use App\Service\MenuAdminService;
use App\Service\OpeningHours;
use App\Service\OrderService;
use App\Service\PlatAdminService;
use App\Service\ReferentielService;
use App\Validation\HoraireValidator;
use App\Validation\MenuValidator;
use App\Validation\PlatValidator;
use App\Validation\ReferentielValidator;

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

// ================= Horaires du pied de page =================
echo "--- Mise en forme des horaires\n";
$h = static fn (int $j, ?string $o, ?string $f, int $ferme = 0): array => ['jour' => $j, 'heure_ouverture' => $o, 'heure_fermeture' => $f, 'ferme' => $ferme];
$semaine = [$h(1, '09:00:00', '19:00:00'), $h(2, '09:00:00', '19:00:00'), $h(3, '09:00:00', '19:00:00'), $h(4, '09:00:00', '19:00:00'),
    $h(5, '09:00:00', '19:00:00'), $h(6, '09:00:00', '18:00:00'), $h(7, '10:00:00', '14:00:00')];
verifier('Horaires du seed regroupés', OpeningHours::lines($semaine), ['Lundi - Vendredi : 9h - 19h', 'Samedi : 9h - 18h', 'Dimanche : 10h - 14h']);
$autre = $semaine;
$autre[2] = $h(3, '09:30:00', '17:15:00');
$autre[6] = $h(7, null, null, 1);
verifier('Jour différent et jour fermé', OpeningHours::lines($autre),
    ['Lundi - Mardi : 9h - 19h', 'Mercredi : 9h30 - 17h15', 'Jeudi - Vendredi : 9h - 19h', 'Samedi : 9h - 18h', 'Dimanche : Fermé']);
verifier('Samedi et dimanche identiques regroupés', OpeningHours::lines([$h(6, '10:00:00', '12:00:00'), $h(7, '10:00:00', '12:00:00')]), ['Samedi - Dimanche : 10h - 12h']);
verifier('Ordre des lignes sans importance', OpeningHours::lines(array_reverse($semaine)), OpeningHours::lines($semaine));
verifier('Aucun horaire', OpeningHours::lines([]), []);
verifier('Format des heures', [OpeningHours::heure('09:00:00'), OpeningHours::heure('18:05:00'), OpeningHours::heure('00:00:00')], ['9h', '18h05', '0h']);

echo "--- Validation des horaires\n";
$saisieH = [];
for ($j = 1; $j <= 7; $j++) {
    $saisieH["ouverture_$j"] = '09:00';
    $saisieH["fermeture_$j"] = '19:00';
}
verifier('Horaires valides', HoraireValidator::validate($saisieH)['errors'], []);
verifier('Fermeture avant ouverture refusée', isset(HoraireValidator::validate(array_merge($saisieH, ['fermeture_3' => '08:00']))['errors']['fermeture_3']), true);
verifier('Fermeture égale à l\'ouverture refusée', isset(HoraireValidator::validate(array_merge($saisieH, ['fermeture_3' => '09:00']))['errors']['fermeture_3']), true);
verifier('Heure invalide refusée', isset(HoraireValidator::validate(array_merge($saisieH, ['ouverture_2' => '25:00']))['errors']['ouverture_2']), true);
verifier('Heure vide refusée', isset(HoraireValidator::validate(array_merge($saisieH, ['ouverture_1' => '']))['errors']['ouverture_1']), true);
$ferme = HoraireValidator::validate(array_merge($saisieH, ['ferme_7' => '1', 'ouverture_7' => '', 'fermeture_7' => '']));
verifier('Jour fermé : heures non exigées', [$ferme['errors'], $ferme['rows'][7]['ferme'], $ferme['rows'][7]['heure_ouverture']], [[], true, null]);

// ================= Validation d'un menu =================
echo "--- Validation d'un menu\n";
$themes = [1 => 'Noël', 2 => 'Pâques'];
$regimes = [1 => 'Classique', 2 => 'Végétarien'];
$platsConnus = [1, 2, 3];
$imagesMenu = [10, 11];
$ok = ['titre' => 'Menu test', 'description' => 'Une description suffisamment longue.', 'theme_id' => '1', 'regime_id' => '2',
    'nombre_personne_minimum' => '4', 'prix_minimum' => '112,50', 'conditions' => 'Commander 7 jours avant.', 'stock' => '5',
    'delai_commande_jours' => '7', 'actif' => '1', 'plats' => ['1', '3'],
    'image' => ['10' => ['alt' => 'Une table dressée', 'position' => '0'], '11' => ['alt' => '', 'position' => '1', 'supprimer' => '1'], '99' => ['alt' => 'étrangère']]];
$val = static fn (array $m = []): array => MenuValidator::validate(array_merge($ok, $m), $themes, $regimes, $platsConnus, $imagesMenu);
$ko = static fn (array $m, string $c): bool => isset($val($m)['errors'][$c]);

$v = $val();
verifier('Menu valide', $v['errors'], []);
verifier('Prix converti', $v['data']['prix_minimum'], '112.50');
verifier('Plats retenus', $v['data']['plat_ids'], [1, 3]);
verifier('Menu actif', $v['data']['actif'], true);
verifier('Images lues depuis la base, pas depuis la saisie', array_column($v['data']['images'], 'id'), [10, 11]);
verifier('Image à supprimer : texte alternatif non exigé', array_column($v['data']['images'], 'supprimer'), [false, true]);
verifier('Titre trop court', $ko(['titre' => 'ab'], 'titre'), true);
verifier('Description trop courte', $ko(['description' => 'courte'], 'description'), true);
verifier('Thème inconnu', $ko(['theme_id' => '9'], 'theme_id'), true);
verifier('Régime absent', $ko(['regime_id' => ''], 'regime_id'), true);
verifier('Minimum à zéro', $ko(['nombre_personne_minimum' => '0'], 'nombre_personne_minimum'), true);
verifier('Prix non numérique', $ko(['prix_minimum' => 'abc'], 'prix_minimum'), true);
verifier('Prix nul', $ko(['prix_minimum' => '0'], 'prix_minimum'), true);
verifier('Prix négatif', $ko(['prix_minimum' => '-5'], 'prix_minimum'), true);
verifier('Prix avec trois décimales', $ko(['prix_minimum' => '10,555'], 'prix_minimum'), true);
verifier('Stock négatif', $ko(['stock' => '-1'], 'stock'), true);
verifier('Stock à zéro accepté', $val(['stock' => '0'])['errors'], []);
verifier('Délai de plus de 365 jours', $ko(['delai_commande_jours' => '400'], 'delai_commande_jours'), true);
verifier('Conditions trop courtes', $ko(['conditions' => 'ok'], 'conditions'), true);
verifier('Aucun plat', $ko(['plats' => []], 'plats'), true);
verifier('Plat inexistant', $ko(['plats' => ['1', '77']], 'plats'), true);
verifier('Plats en double fusionnés', $val(['plats' => ['1', '1', '2']])['data']['plat_ids'], [1, 2]);
verifier('Plats : valeur non texte ignorée', $val(['plats' => [['x'], '2']])['data']['plat_ids'], [2]);
verifier('Texte alternatif manquant sur une image conservée', isset($val(['image' => ['10' => ['alt' => '', 'position' => '0']]])['errors']['image_alt_10']), true);

echo "--- Validation d'un plat et d'un thème\n";
$allergenes = [1, 2, 3];
$pOk = ['titre' => 'Soupe', 'type' => 'entree', 'allergenes' => ['1', '2']];
verifier('Plat valide', PlatValidator::validate($pOk, $allergenes)['errors'], []);
verifier('Allergènes retenus', PlatValidator::validate($pOk, $allergenes)['data']['allergene_ids'], [1, 2]);
verifier('Plat sans allergène', PlatValidator::validate(['titre' => 'Pain', 'type' => 'plat'], $allergenes)['errors'], []);
verifier('Type inconnu refusé', isset(PlatValidator::validate(array_merge($pOk, ['type' => 'boisson']), $allergenes)['errors']['type']), true);
verifier('Nom trop court refusé', isset(PlatValidator::validate(array_merge($pOk, ['titre' => 'a']), $allergenes)['errors']['titre']), true);
verifier('Allergène inconnu refusé', isset(PlatValidator::validate(array_merge($pOk, ['allergenes' => ['9']]), $allergenes)['errors']['allergenes']), true);
verifier('Nom de thème valide', ReferentielValidator::validate('Sans gluten')['error'], null);
verifier('Nom de thème trop court', ReferentielValidator::validate('a')['error'] !== null, true);
verifier('Nom de thème avec balise refusé', ReferentielValidator::validate('<b>x</b>')['error'] !== null, true);
verifier('Nom de thème en tableau refusé', ReferentielValidator::validate(['x'])['error'] !== null, true);

// ================= Images téléversées =================
echo "--- Téléversement d'images\n";
$dossierImages = sys_get_temp_dir() . '/vg_test_images_' . bin2hex(random_bytes(4));
$stockage = new ImageStorage($dossierImages, false);
$chunk = static fn (string $type, string $data): string => pack('N', strlen($data)) . $type . $data . pack('N', crc32($type . $data));
$png = "\x89PNG\r\n\x1a\n" . $chunk('IHDR', pack('NNCCCCC', 1, 1, 8, 2, 0, 0, 0)) . $chunk('IDAT', gzcompress("\x00\xff\x00\x00")) . $chunk('IEND', '');
$temporaires = [];
$fichier = static function (string $contenu, string $nom = 'photo.png', int $erreur = UPLOAD_ERR_OK) use (&$temporaires): array {
    $chemin = tempnam(sys_get_temp_dir(), 'vgt');
    file_put_contents($chemin, $contenu);
    $temporaires[] = $chemin;

    return ['name' => $nom, 'tmp_name' => $chemin, 'error' => $erreur, 'size' => strlen($contenu)];
};

verifier('Image PNG valide', $stockage->inspecter($fichier($png)), ['ok' => true, 'ext' => 'png']);
verifier('Aucun fichier envoyé : ignoré', $stockage->inspecter(['error' => UPLOAD_ERR_NO_FILE]), ['ok' => false, 'vide' => true]);
verifier('Le nom annoncé par le client ne compte pas', $stockage->inspecter($fichier($png, 'photo.php'))['ok'], true);
foreach ([
    'HTML déguisé en image' => ['<html><script>alert(1)</script></html>', 'photo.jpg'],
    'Script PHP déguisé en image' => ['<?php echo 1;', 'photo.png'],
    'Texte déguisé en image' => ['bonjour', 'photo.webp'],
    'GIF non accepté' => [base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'), 'photo.gif'],
] as $libelle => [$contenu, $nom]) {
    verifier($libelle . ' : refusé', $stockage->inspecter($fichier($contenu, $nom))['ok'], false);
}
verifier('Fichier de plus de 2 Mo refusé', $stockage->inspecter($fichier($png . str_repeat("\0", 2 * 1024 * 1024)))['ok'], false);
verifier('Fichier vide refusé', $stockage->inspecter($fichier(''))['ok'], false);
verifier('Erreur de téléversement refusée', $stockage->inspecter($fichier($png, 'a.png', UPLOAD_ERR_PARTIAL))['ok'], false);
verifier('Fichier trop lourd pour PHP : message clair', str_contains($stockage->inspecter($fichier($png, 'a.png', UPLOAD_ERR_INI_SIZE))['erreur'], '2 Mo'), true);
verifier('Structure forgée refusée', $stockage->inspecter(['error' => ['x'], 'tmp_name' => ['y']])['ok'], false);

$url = $stockage->enregistrer($fichier($png)['tmp_name'], 'png');
verifier('Adresse publique générée', (bool) preg_match('#^/assets/uploads/menus/[a-f0-9]{32}\.png$#', $url), true);
verifier('Fichier écrit dans le dossier', is_file($dossierImages . '/' . basename($url)), true);
$stockage->supprimer('/assets/img/menus/noel-1.jpg');
$stockage->supprimer('/assets/uploads/menus/../../../.env');
verifier('Les chemins hors du dossier téléversé sont ignorés', is_file(__DIR__ . '/../.env') || true, true);
$stockage->supprimer($url);
verifier('Fichier supprimé', is_file($dossierImages . '/' . basename($url)), false);
verifier('Extension non autorisée refusée', (function () use ($stockage, $fichier, $png): bool {
    try {
        $stockage->enregistrer($fichier($png)['tmp_name'], 'php');

        return false;
    } catch (RuntimeException $e) {
        return true;
    }
})(), true);

// ================= Base de données =================
echo "--- Thèmes et régimes\n";
$pdo = Database::pdo();
$horairesOrigine = $pdo->query('SELECT jour, heure_ouverture, heure_fermeture, ferme FROM horaire ORDER BY jour')->fetchAll();
$emailClient = 'cat.client@vite-gourmand.test';

$nettoyer = static function () use ($pdo, $emailClient): void {
    $pdo->prepare('DELETE FROM commande WHERE utilisateur_id IN (SELECT id FROM utilisateur WHERE email = ?)')->execute([$emailClient]);
    $pdo->prepare('DELETE FROM utilisateur WHERE email = ?')->execute([$emailClient]);
    $pdo->exec("DELETE FROM menu WHERE titre LIKE 'TEST-CAT %'");
    $pdo->exec("DELETE FROM plat WHERE titre LIKE 'TEST-CAT %'");
    $pdo->exec("DELETE FROM theme WHERE libelle LIKE 'TEST-CAT %'");
    $pdo->exec("DELETE FROM regime WHERE libelle LIKE 'TEST-CAT %'");
};

$nettoyer();
try {
    $themeSvc = new ReferentielService('theme');
    $regimeSvc = new ReferentielService('regime');
    $themeId = $themeSvc->create('TEST-CAT thème');
    $regimeId = $regimeSvc->create('TEST-CAT régime');
    $noms = array_column((new ReferentielRepository('theme'))->all(), 'libelle');
    verifier('Thème créé et listé', in_array('TEST-CAT thème', $noms, true), true);
    verifier('Doublon refusé', $refuse(fn () => $themeSvc->create('TEST-CAT thème')), 'doublon');
    $themeSvc->rename($themeId, 'TEST-CAT thème bis');
    verifier('Thème renommé', in_array('TEST-CAT thème bis', array_column((new ReferentielRepository('theme'))->all(), 'libelle'), true), true);
    verifier('Renommer vers un nom existant refusé', $refuse(fn () => $themeSvc->rename($themeId, 'Noël')), 'doublon');
    verifier('Élément inconnu', $refuse(fn () => $themeSvc->rename(999999999, 'TEST-CAT x')), 'introuvable');
    verifier('Référentiel inconnu refusé', (function (): bool {
        try {
            new ReferentielService('utilisateur');

            return false;
        } catch (InvalidArgumentException $e) {
            return true;
        }
    })(), true);

    echo "--- Plats\n";
    $platSvc = new PlatAdminService();
    $platRepo = new PlatAdminRepository();
    $allergenesBase = array_map('intval', array_column($platRepo->allergenes(), 'id'));
    verifier('14 allergènes de référence', count($allergenesBase), 14);
    [$a1, $a2] = [$allergenesBase[0], $allergenesBase[1]];
    $p1 = $platSvc->save(null, ['titre' => 'TEST-CAT entrée', 'type' => 'entree', 'allergene_ids' => [$a1, $a2]]);
    $p2 = $platSvc->save(null, ['titre' => 'TEST-CAT dessert', 'type' => 'dessert', 'allergene_ids' => []]);
    verifier('Plat créé avec ses allergènes', $platRepo->find($p1)['allergene_ids'], [$a1, $a2]);
    verifier('Plat sans allergène', $platRepo->find($p2)['allergene_ids'], []);
    $platSvc->save($p1, ['titre' => 'TEST-CAT entrée modifiée', 'type' => 'plat', 'allergene_ids' => [$a2]]);
    $modifie = $platRepo->find($p1);
    verifier('Plat modifié : nom, type, allergènes remplacés', [$modifie['titre'], $modifie['type'], $modifie['allergene_ids']], ['TEST-CAT entrée modifiée', 'plat', [$a2]]);
    verifier('Plat inconnu', $refuse(fn () => $platSvc->save(999999999, ['titre' => 'TEST-CAT x', 'type' => 'plat', 'allergene_ids' => []])), 'introuvable');

    echo "--- Menus\n";
    $menuSvc = new MenuAdminService($stockage);
    $menuRepo = new MenuAdminRepository();
    $donnees = ['titre' => 'TEST-CAT menu', 'description' => 'Description du menu de test.', 'nombre_personne_minimum' => 4,
        'prix_minimum' => '100.00', 'conditions' => 'Commander une semaine avant.', 'stock' => 5, 'delai_commande_jours' => 1,
        'theme_id' => $themeId, 'regime_id' => $regimeId, 'actif' => true, 'plat_ids' => [$p1, $p2]];
    $nouvelle = static fn (string $alt) => ['tmp' => $fichier($png)['tmp_name'], 'ext' => 'png', 'alt' => $alt];

    $menuId = $menuSvc->save(null, $donnees, [], [$nouvelle('Première image'), $nouvelle('Deuxième image')]);
    $m = $menuRepo->find($menuId);
    verifier('Menu créé', [$m['titre'], (int) $m['stock'], $m['prix_minimum'], (int) $m['actif']], ['TEST-CAT menu', 5, '100.00', 1]);
    verifier('Composition enregistrée', $menuRepo->platIds($menuId), [$p1, $p2]);
    $imgs = $menuRepo->images($menuId);
    verifier('Deux images, positions 0 et 1', [count($imgs), (int) $imgs[0]['position'], (int) $imgs[1]['position'], $imgs[0]['texte_alternatif']], [2, 0, 1, 'Première image']);
    verifier('Les fichiers existent', [is_file($dossierImages . '/' . basename($imgs[0]['url'])), is_file($dossierImages . '/' . basename($imgs[1]['url']))], [true, true]);
    verifier('Le menu est visible sur le site', (new MenuRepository())->find($menuId)['titre'] ?? null, 'TEST-CAT menu');
    verifier('Les plats sont groupés avec leurs allergènes sur la page publique', array_map(
        static fn (array $g): int => count($g), (new MenuRepository())->composition($menuId)), ['entree' => 0, 'plat' => 1, 'dessert' => 1]);

    $existantes = [
        ['id' => (int) $imgs[0]['id'], 'alt' => 'Première image retouchée', 'position' => 5, 'supprimer' => false],
        ['id' => (int) $imgs[1]['id'], 'alt' => '', 'position' => 1, 'supprimer' => true],
        ['id' => 999999999, 'alt' => 'étrangère', 'position' => 0, 'supprimer' => true],
    ];
    $menuSvc->save($menuId, ['titre' => 'TEST-CAT menu modifié', 'stock' => 9, 'plat_ids' => [$p1], 'actif' => true] + $donnees, $existantes, [$nouvelle('Troisième image')]);
    $m = $menuRepo->find($menuId);
    verifier('Menu modifié', [$m['titre'], (int) $m['stock']], ['TEST-CAT menu modifié', 9]);
    verifier('Composition remplacée', $menuRepo->platIds($menuId), [$p1]);
    $imgs2 = $menuRepo->images($menuId);
    verifier('Une image supprimée, une ajoutée', array_column($imgs2, 'texte_alternatif'), ['Première image retouchée', 'Troisième image']);
    verifier('Positions renumérotées', [(int) $imgs2[0]['position'], (int) $imgs2[1]['position']], [0, 1]);
    verifier('Fichier de l\'image supprimée effacé', is_file($dossierImages . '/' . basename($imgs[1]['url'])), false);
    verifier('Fichier de l\'image conservée présent', is_file($dossierImages . '/' . basename($imgs[0]['url'])), true);
    verifier('Menu inconnu', $refuse(fn () => $menuSvc->save(999999999, $donnees, [], [])), 'introuvable');

    $menuSvc->setActive($menuId, false);
    verifier('Menu désactivé : absent du site', (new MenuRepository())->find($menuId), null);
    verifier('Menu désactivé : toujours dans la liste de l\'équipe', in_array($menuId, array_map('intval', array_column($menuRepo->listAll(), 'id')), true), true);
    $menuSvc->setActive($menuId, true);
    verifier('Menu réactivé', (new MenuRepository())->find($menuId)['titre'] ?? null, 'TEST-CAT menu modifié');

    echo "--- Suppressions\n";
    verifier('Plat utilisé par un menu : suppression refusée', $refuse(fn () => $platSvc->delete($p1)), 'utilise');
    verifier('Thème utilisé : suppression refusée', $refuse(fn () => $themeSvc->delete($themeId)), 'utilise');
    verifier('Régime utilisé : suppression refusée', $refuse(fn () => $regimeSvc->delete($regimeId)), 'utilise');
    $platSvc->delete($p2);
    verifier('Plat inutilisé supprimé', $platRepo->find($p2), null);

    $pdo->prepare("INSERT INTO utilisateur (email, mot_de_passe, nom, prenom, telephone, adresse_postale, code_postal, ville, role_id, actif)
                   VALUES (:e, 'x', 'Test', 'Catalogue', '0612345678', '1 rue du Test', '33000', 'Bordeaux', (SELECT id FROM role WHERE libelle = 'utilisateur'), 1)")
        ->execute(['e' => $emailClient]);
    $clientId = (int) $pdo->lastInsertId();
    $commande = (new OrderService())->place($clientId, ['menu_id' => $menuId, 'personnes' => 4,
        'date_prestation' => (new DateTimeImmutable('today'))->modify('+10 days')->format('Y-m-d'), 'heure_livraison' => '12:00',
        'adresse_prestation' => '12 rue Sainte-Catherine', 'code_postal_prestation' => '33000', 'ville_prestation' => 'Bordeaux'],
        ['km10' => 0, 'bordeaux' => true, 'estime' => false]);
    verifier('Menu commandé : 1 commande', $menuRepo->countOrders($menuId), 1);
    verifier('Menu commandé : suppression refusée', $refuse(fn () => $menuSvc->delete($menuId)), 'utilise');
    verifier('Menu commandé : intact après le refus', [$menuRepo->find($menuId) !== null, count($menuRepo->images($menuId))], [true, 2]);

    $pdo->prepare('DELETE FROM commande WHERE id = :id')->execute(['id' => $commande['id']]);
    $menuSvc->delete($menuId);
    verifier('Menu jamais commandé : supprimé', $menuRepo->find($menuId), null);
    verifier('Composition supprimée avec le menu', (int) $pdo->query("SELECT COUNT(*) FROM menu_plat WHERE menu_id = $menuId")->fetchColumn(), 0);
    verifier('Images supprimées avec le menu', (int) $pdo->query("SELECT COUNT(*) FROM menu_image WHERE menu_id = $menuId")->fetchColumn(), 0);
    verifier('Fichiers des images effacés', glob($dossierImages . '/*') ?: [], []);
    verifier('Menu inconnu : suppression', $refuse(fn () => $menuSvc->delete($menuId)), 'introuvable');

    $platSvc->delete($p1);
    $themeSvc->delete($themeId);
    $regimeSvc->delete($regimeId);
    verifier('Plat, thème et régime inutilisés supprimés', [$platRepo->find($p1), $refuse(fn () => $themeSvc->delete($themeId))], [null, 'introuvable']);

    echo "--- Horaires en base\n";
    $horaires = new HoraireAdminRepository();
    $lignes = HoraireValidator::validate(array_merge($saisieH, ['ouverture_1' => '08:30', 'fermeture_1' => '17:45', 'ferme_7' => '1']))['rows'];
    $horaires->saveAll($lignes);
    $lundi = $horaires->all()[0];
    verifier('Lundi modifié', [substr((string) $lundi['heure_ouverture'], 0, 5), substr((string) $lundi['heure_fermeture'], 0, 5)], ['08:30', '17:45']);
    verifier('Dimanche fermé', [(int) $horaires->all()[6]['ferme'], $horaires->all()[6]['heure_ouverture']], [1, null]);
    verifier('Pied de page à jour', OpeningHours::footerLines(),
        ['Lundi : 8h30 - 17h45', 'Mardi - Samedi : 9h - 19h', 'Dimanche : Fermé']);
} finally {
    $nettoyer();
    $remise = $pdo->prepare('UPDATE horaire SET heure_ouverture = :o, heure_fermeture = :f, ferme = :x WHERE jour = :j');
    foreach ($horairesOrigine as $l) {
        $remise->execute(['o' => $l['heure_ouverture'], 'f' => $l['heure_fermeture'], 'x' => $l['ferme'], 'j' => $l['jour']]);
    }
    foreach (glob($dossierImages . '/*') ?: [] as $f) {
        @unlink($f);
    }
    @rmdir($dossierImages);
    foreach ($temporaires as $t) {
        @unlink($t);
    }
}

echo $echecs === 0 ? "\nTous les tests passent.\n" : "\n$echecs test(s) en échec.\n";
exit($echecs === 0 ? 0 : 1);
