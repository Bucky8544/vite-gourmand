<?php
declare(strict_types=1);

// Envoie un vrai e-mail de test avec la configuration courante (variables SMTP_* et MAIL_FROM).
//   php scripts/send_test_mail.php destinataire@exemple.fr
require __DIR__ . '/../vendor/autoload.php';

use App\Service\Mailer;
use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;

$destinataire = trim($argv[1] ?? '');
if (!filter_var($destinataire, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Indiquez l'adresse du destinataire : php scripts/send_test_mail.php vous@exemple.fr\n");
    exit(1);
}

$hote = (string) getenv('SMTP_HOST');
$port = (int) getenv('SMTP_PORT');
$utilisateur = (string) getenv('SMTP_USER');
$cle = (string) getenv('SMTP_PASS');
$etat = static fn (string $v): string => $v !== '' ? 'défini' : 'VIDE';

echo "Serveur d'envoi : " . ($hote !== '' ? $hote : '(non défini)') . ':' . ($port > 0 ? $port : '(non défini)') . "\n";
echo 'Identifiant SMTP : ' . $etat($utilisateur) . ' | clé SMTP : ' . $etat($cle) . ' | expéditeur MAIL_FROM : ' . $etat((string) getenv('MAIL_FROM')) . "\n";

// 1. Connexion et authentification, indépendamment du reste de l'application
if ($hote === '' || $port === 1025) {
    echo "1. Connexion au serveur : ignorée (serveur local de développement)\n";
} else {
    $m = new PHPMailer(true);
    $m->isSMTP();
    $m->Host = $hote;
    $m->Port = $port;
    $m->SMTPAuth = $utilisateur !== '';
    $m->Username = $utilisateur;
    $m->Password = $cle;
    $m->Timeout = 15;
    $m->SMTPSecure = $port === 465 ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
    try {
        $m->smtpConnect();
        $m->smtpClose();
        echo "1. Connexion et authentification au serveur d'envoi : OK\n";
    } catch (MailException $e) {
        fwrite(STDERR, "1. ÉCHEC de la connexion ou de l'authentification : " . $e->getMessage() . "\n");
        exit(1);
    }
}

// 2. Envoi réel avec le service de l'application
try {
    (new Mailer())->send(
        $destinataire,
        '',
        "Test d'envoi Vite & Gourmand",
        '<p>Ceci est un message de test envoyé depuis le site Vite &amp; Gourmand.</p>',
        'Ceci est un message de test envoyé depuis le site Vite & Gourmand.'
    );
    echo "2. Message accepté par le serveur d'envoi : vérifiez la boîte de $destinataire, y compris les courriers indésirables.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "2. ÉCHEC de l'envoi : " . $e->getMessage() . "\n");
    exit(1);
}
