<?php
declare(strict_types=1);

namespace App\Service;

use Closure;

/** Mails automatiques déclenchés par les changements de statut. */
final class StaffMailer
{
    private Closure $send;

    /** $send : fonction (to, toName, subject, html, text), remplaçable dans les tests. */
    public function __construct(?Closure $send = null)
    {
        $this->send = $send ?? static fn (string $to, string $nom, string $sujet, string $html, string $texte) => (new Mailer())->send($to, $nom, $sujet, $html, $texte);
    }

    /** Commande terminée : le client est invité à se connecter pour donner son avis. */
    public function sendReviewInvitation(array $c): void
    {
        $url = rtrim(getenv('APP_URL') ?: 'http://localhost:8081', '/') . '/mon-espace/commandes/' . $c['numero_commande'];
        $this->envoyer($c, 'Votre commande est terminée : donnez votre avis',
            ["Votre commande n° {$c['numero_commande']} ({$c['menu_titre']}) est terminée. Nous espérons que tout s'est bien passé !",
             "Vous pouvez vous connecter à votre compte pour nous donner votre avis (une note de 1 à 5 et un commentaire) : $url"]);
    }

    /** Matériel prêté : rappel des 600 € de frais si le matériel n'est pas restitué sous 10 jours ouvrés. */
    public function sendMaterialReminder(array $c): void
    {
        $this->envoyer($c, 'Retour du matériel prêté pour votre commande',
            ["Du matériel vous a été prêté pour votre commande n° {$c['numero_commande']}.",
             "Si le matériel n'est pas restitué sous 10 jours ouvrés, des frais de 600 € vous seront facturés (voir nos conditions générales de vente).",
             "Pour rendre le matériel, merci de prendre contact avec nous en répondant à ce message."]);
    }

    /** Commande annulée par l'équipe, après contact avec le client. */
    public function sendCancellation(array $c): void
    {
        $contact = ($c['mode_contact_annulation'] ?? '') === 'gsm' ? 'par téléphone' : 'par e-mail';
        $this->envoyer($c, 'Annulation de votre commande ' . $c['numero_commande'],
            ["Votre commande n° {$c['numero_commande']} ({$c['menu_titre']}) a été annulée. Nous vous avons contacté $contact.",
             'Motif : ' . ($c['motif_annulation'] ?? ''),
             "N'hésitez pas à nous contacter pour toute question."]);
    }

    /** @param list<string> $paragraphes */
    private function envoyer(array $c, string $sujet, array $paragraphes): void
    {
        $bonjour = !empty($c['prenom']) ? 'Bonjour ' . $c['prenom'] . ',' : 'Bonjour,';
        $texte = $bonjour . "\n\n" . implode("\n\n", $paragraphes) . "\n\nÀ très bientôt,\nJulie et José";
        $html = '<p>' . htmlspecialchars($bonjour, ENT_QUOTES, 'UTF-8') . '</p>'
            . implode('', array_map(static fn (string $p): string => '<p>' . nl2br(htmlspecialchars($p, ENT_QUOTES, 'UTF-8')) . '</p>', $paragraphes))
            . '<p>À très bientôt,<br>Julie et José</p>';

        ($this->send)((string) $c['email'], (string) ($c['prenom'] ?? ''), $sujet, $html, $texte);
    }
}
