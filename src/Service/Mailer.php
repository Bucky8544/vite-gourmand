<?php
declare(strict_types=1);

namespace App\Service;

use PHPMailer\PHPMailer\PHPMailer;

final class Mailer
{
    public function send(string $to, string $toName, string $subject, string $html, string $text): void
    {
        $m = new PHPMailer(true);
        $m->isSMTP();
        $m->Host = getenv('SMTP_HOST') ?: 'mailpit';
        $m->Port = (int) (getenv('SMTP_PORT') ?: 1025);
        $m->CharSet = 'UTF-8';

        $user = getenv('SMTP_USER');
        if ($user) {                       // production : authentification + STARTTLS
            $m->SMTPAuth = true;
            $m->Username = $user;
            $m->Password = (string) getenv('SMTP_PASS');
            $m->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } else {                           // développement (Mailpit) : sans authentification
            $m->SMTPAuth = false;
            $m->SMTPAutoTLS = false;
        }

        $m->setFrom(getenv('MAIL_FROM') ?: 'no-reply@vite-gourmand.test', getenv('MAIL_FROM_NAME') ?: 'Vite & Gourmand');
        $m->addAddress($to, $toName);
        $m->Subject = $subject;
        $m->isHTML(true);
        $m->Body = $html;
        $m->AltBody = $text;
        $m->send();
    }

    public function sendWelcome(string $email, string $prenom): void
    {
        $url = rtrim(getenv('APP_URL') ?: 'http://localhost:8081', '/') . '/connexion';
        $p = htmlspecialchars($prenom, ENT_QUOTES, 'UTF-8');

        $html = "<p>Bonjour $p,</p>"
            . '<p>Bienvenue chez Vite &amp; Gourmand ! Votre compte a bien été créé.</p>'
            . "<p>Vous pouvez dès maintenant <a href=\"$url\">vous connecter</a> pour découvrir nos menus et passer commande.</p>"
            . '<p>À très bientôt,<br>Julie et José</p>';
        $text = "Bonjour $prenom,\n\nBienvenue chez Vite & Gourmand ! Votre compte a bien été créé.\n"
            . "Connectez-vous : $url\n\nÀ très bientôt,\nJulie et José";

        $this->send($email, $prenom, 'Bienvenue chez Vite & Gourmand', $html, $text);
    }
}
