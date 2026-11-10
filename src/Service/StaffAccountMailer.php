<?php
declare(strict_types=1);

namespace App\Service;

use Closure;

final class StaffAccountMailer
{
    private Closure $send;

    /** $send : fonction (to, toName, subject, html, text), remplaçable dans les tests. */
    public function __construct(?Closure $send = null)
    {
        $this->send = $send ?? static fn (string $to, string $nom, string $sujet, string $html, string $texte) => (new Mailer())->send($to, $nom, $sujet, $html, $texte);
    }

    /** Le mot de passe n'est jamais dans le message : l'employé doit le demander à l'administrateur. */
    public function sendAccountCreated(string $email): void
    {
        $url = rtrim(getenv('APP_URL') ?: 'http://localhost:8081', '/') . '/connexion';
        $texte = "Bonjour,\n\nUn compte employé vient d'être créé pour vous sur le site de Vite & Gourmand.\n\n"
            . "Identifiant : $email\nConnexion : $url\n\n"
            . "Pour des raisons de sécurité, le mot de passe n'est pas communiqué dans ce message : "
            . "rapprochez-vous de l'administrateur pour l'obtenir.\n\nÀ bientôt,\nVite & Gourmand";
        $e = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        $html = '<p>Bonjour,</p><p>Un compte employé vient d\'être créé pour vous sur le site de Vite &amp; Gourmand.</p>'
            . '<p>Identifiant : <strong>' . $e($email) . '</strong><br>Connexion : <a href="' . $e($url) . '">' . $e($url) . '</a></p>'
            . '<p>Pour des raisons de sécurité, le mot de passe n\'est pas communiqué dans ce message : '
            . 'rapprochez-vous de l\'administrateur pour l\'obtenir.</p><p>À bientôt,<br>Vite &amp; Gourmand</p>';

        ($this->send)($email, '', 'Votre compte employé Vite & Gourmand', $html, $texte);
    }
}
