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

    public function sendPasswordReset(string $email, ?string $prenom, string $url): void
    {
        $minutes = PasswordResetService::TTL_MINUTES;
        $u = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');

        $html = '<p>' . self::hello($prenom) . '</p>'
            . '<p>Vous avez demandé à réinitialiser le mot de passe de votre compte Vite &amp; Gourmand.</p>'
            . "<p><a href=\"$u\">Choisir un nouveau mot de passe</a></p>"
            . "<p>Ce lien est valable $minutes minutes et ne peut être utilisé qu'une seule fois.</p>"
            . "<p>Si vous n'êtes pas à l'origine de cette demande, ignorez ce message : votre mot de passe reste inchangé.</p>";
        $text = self::helloText($prenom) . "\n\nVous avez demandé à réinitialiser le mot de passe de votre compte Vite & Gourmand.\n"
            . "Choisissez un nouveau mot de passe : $url\n\n"
            . "Ce lien est valable $minutes minutes et ne peut être utilisé qu'une seule fois.\n"
            . "Si vous n'êtes pas à l'origine de cette demande, ignorez ce message : votre mot de passe reste inchangé.";

        $this->send($email, (string) $prenom, 'Réinitialisation de votre mot de passe', $html, $text);
    }

    public function sendPasswordChanged(string $email, ?string $prenom): void
    {
        $html = '<p>' . self::hello($prenom) . '</p>'
            . '<p>Le mot de passe de votre compte Vite &amp; Gourmand vient d\'être modifié.</p>'
            . "<p>Si vous n'êtes pas à l'origine de ce changement, contactez-nous sans attendre.</p>";
        $text = self::helloText($prenom) . "\n\nLe mot de passe de votre compte Vite & Gourmand vient d'être modifié.\n"
            . "Si vous n'êtes pas à l'origine de ce changement, contactez-nous sans attendre.";

        $this->send($email, (string) $prenom, 'Votre mot de passe a été modifié', $html, $text);
    }

    private static function hello(?string $prenom): string
    {
        return $prenom !== null && $prenom !== '' ? 'Bonjour ' . htmlspecialchars($prenom, ENT_QUOTES, 'UTF-8') . ',' : 'Bonjour,';
    }

    private static function helloText(?string $prenom): string
    {
        return $prenom !== null && $prenom !== '' ? "Bonjour $prenom," : 'Bonjour,';
    }
}
