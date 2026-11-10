<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Entreprise;
use App\Service\ContactMailer;
use App\Service\ContactThrottle;
use App\Validation\ContactValidator;

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

// ================= Validation =================
echo "--- Validation du formulaire de contact\n";
$ok = ['email' => 'Visiteur@Exemple.FR', 'titre' => 'Question sur un menu', 'description' => "Bonjour,\r\nPouvez-vous me renseigner ?"];
$v = ContactValidator::validate($ok);
verifier('Message valide', $v['errors'], []);
verifier('E-mail en minuscules', $v['data']['email'], 'visiteur@exemple.fr');
verifier('Sauts de ligne normalisés', $v['data']['description'], "Bonjour,\nPouvez-vous me renseigner ?");
$ko = static fn (array $m, string $c): bool => isset(ContactValidator::validate(array_merge($ok, $m))['errors'][$c]);
verifier('E-mail vide refusé', $ko(['email' => ''], 'email'), true);
verifier('E-mail invalide refusé', $ko(['email' => 'pas-un-email'], 'email'), true);
verifier('E-mail avec saut de ligne refusé (injection d\'en-têtes)', $ko(['email' => "a@b.fr\r\nBcc: victime@exemple.fr"], 'email'), true);
verifier('Objet avec saut de ligne refusé (injection d\'en-têtes)', $ko(['titre' => "Bonjour\r\nBcc: victime@exemple.fr"], 'titre'), true);
verifier('Objet avec saut de ligne seul refusé', $ko(['titre' => "Bonjour\nBcc: x@y.fr"], 'titre'), true);
verifier('Objet trop court refusé', $ko(['titre' => 'ab'], 'titre'), true);
verifier('Objet trop long refusé', $ko(['titre' => str_repeat('a', 151)], 'titre'), true);
verifier('Message trop court refusé', $ko(['description' => 'court'], 'description'), true);
verifier('Message de 3000 caractères accepté', ContactValidator::validate(array_merge($ok, ['description' => str_repeat('a', 3000)]))['errors'], []);
verifier('Message de 3001 caractères refusé', $ko(['description' => str_repeat('a', 3001)], 'description'), true);
verifier('Octet nul refusé', $ko(['description' => "bonjour\0 tout le monde"], 'description'), true);
verifier('Valeur en tableau refusée', $ko(['titre' => ['x']], 'titre'), true);

// ================= Limitation =================
echo "--- Limitation des envois\n";
$maintenant = 1_000_000;
[$a1, $h1] = ContactThrottle::evaluer(null, $maintenant);
[$a2, $h2] = ContactThrottle::evaluer($h1, $maintenant + 10);
[$a3, $h3] = ContactThrottle::evaluer($h2, $maintenant + 20);
[$a4, $h4] = ContactThrottle::evaluer($h3, $maintenant + 30);
verifier('Trois envois autorisés', [$a1, $a2, $a3], [true, true, true]);
verifier('Le quatrième est refusé', $a4, false);
verifier('Un refus n\'ajoute rien à l\'historique', count($h4), 3);
[$a5] = ContactThrottle::evaluer($h4, $maintenant + ContactThrottle::FENETRE + 1);
verifier('Après 15 minutes : de nouveau autorisé', $a5, true);
verifier('Valeur de session invalide ignorée', ContactThrottle::evaluer('n_importe_quoi', $maintenant)[0], true);
verifier('Valeurs non entières ignorées', count(ContactThrottle::evaluer(['x', 1.5, null, $maintenant - 1], $maintenant)[1]), 2);
verifier('Horodatages futurs ignorés', ContactThrottle::evaluer([$maintenant + 500, $maintenant + 600, $maintenant + 700], $maintenant)[0], true);

// ================= Mail =================
echo "--- Mail envoyé à l'entreprise\n";
$envois = [];
$mailer = new ContactMailer(function (string $to, string $nom, string $sujet, string $html, string $texte, ?string $replyTo) use (&$envois): void {
    $envois[] = compact('to', 'nom', 'sujet', 'html', 'texte', 'replyTo');
});
$mailer->send(['email' => 'visiteur@exemple.fr', 'titre' => 'Question', 'description' => "Un <b>message</b> avec \"guillemets\"\nsur deux lignes."]);
verifier('Un seul mail envoyé', count($envois), 1);
verifier('Destinataire : l\'entreprise', $envois[0]['to'], getenv('CONTACT_TO') ?: Entreprise::get('email'));
verifier('Adresse de réponse : le visiteur', $envois[0]['replyTo'], 'visiteur@exemple.fr');
verifier('Objet préfixé', $envois[0]['sujet'], '[Contact du site] Question');
verifier('Aucun mail envoyé au visiteur', $envois[0]['to'] !== 'visiteur@exemple.fr', true);
verifier('HTML : balises du visiteur échappées', [str_contains($envois[0]['html'], '&lt;b&gt;message&lt;/b&gt;'), str_contains($envois[0]['html'], '<b>message</b>')], [true, false]);
verifier('HTML : retour à la ligne conservé', str_contains($envois[0]['html'], '<br'), true);
verifier('Texte brut : message intact', str_contains($envois[0]['texte'], 'Un <b>message</b> avec "guillemets"'), true);
verifier('Texte brut : expéditeur indiqué', str_contains($envois[0]['texte'], 'De : visiteur@exemple.fr'), true);

// ================= Contenu des pages légales =================
echo "--- Contenu des pages\n";
$page = static fn (string $f): string => (string) file_get_contents(__DIR__ . '/../templates/legal/' . $f . '.php');
$cgv = $page('cgv');
verifier('CGV : frais de 600 euros et 10 jours ouvrés', [str_contains($cgv, '600 euros'), str_contains($cgv, '10 jours ouvrés')], [true, true]);
verifier('CGV : frais de livraison (5 euros et 0,59 euro par km)', [str_contains($cgv, '5 euros'), str_contains($cgv, '0,59 euro')], [true, true]);
verifier('CGV : remise de 10 %', str_contains($cgv, '10 %'), true);
verifier('CGV : rétractation, médiation et droit applicable', [str_contains($cgv, 'L221-28'), str_contains($cgv, 'médiateur'), str_contains($cgv, 'droit français')], [true, true, true]);
verifier('CGV : contact du client avant annulation par l\'entreprise', str_contains($cgv, 'préalablement contacté le client'), true);
$mentions = $page('mentions');
verifier('Mentions : éditeur, directeur de la publication, hébergeur', [str_contains($mentions, 'Éditeur du site'), str_contains($mentions, 'Directeur de la publication'), str_contains($mentions, 'Hébergement')], [true, true, true]);
$conf = $page('confidentialite');
verifier('Confidentialité : responsable, droits, CNIL, durées', [str_contains($conf, 'Responsable du traitement'), str_contains($conf, 'Vos droits'), str_contains($conf, 'CNIL'), str_contains($conf, 'Durées de conservation')], [true, true, true, true]);
verifier('Confidentialité : cookie de session et IGN décrits', [str_contains($conf, 'VGSESSID'), str_contains($conf, 'IGN')], [true, true]);
verifier('Accessibilité : RGAA et voie de recours', [str_contains($page('accessibilite'), 'RGAA'), str_contains($page('accessibilite'), 'Défenseur des droits')], [true, true]);
$entreprise = Entreprise::all();
$cles = ['nom', 'forme', 'capital', 'siren', 'adresse', 'telephone', 'email', 'directeur', 'hebergeur_nom', 'mediateur', 'paiement', 'cgv_date', 'politique_date'];
verifier('Configuration de l\'entreprise : toutes les clés renseignées', array_values(array_filter($cles, static fn (string $c): bool => trim((string) ($entreprise[$c] ?? '')) === '')), []);

echo "\n";
if (Entreprise::fictif()) {
    echo "⚠️  config/entreprise.php contient des informations fictives ('fictif' => true) : à remplacer avant la mise en service.\n";
}
echo $echecs === 0 ? "Tous les tests passent.\n" : "$echecs test(s) en échec.\n";
exit($echecs === 0 ? 0 : 1);
