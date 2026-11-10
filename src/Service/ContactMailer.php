<?php
declare(strict_types=1);

namespace App\Service;

use App\Core\Entreprise;
use Closure;

/** Envoie le message du formulaire de contact à l'entreprise. Rien n'est envoyé au visiteur. */
final class ContactMailer
{
    private Closure $transport;

    /** $transport : fonction (to, toName, subject, html, text, replyTo), remplaçable dans les tests. */
    public function __construct(?Closure $transport = null)
    {
        $this->transport = $transport ?? static fn (string $to, string $nom, string $sujet, string $html, string $texte, ?string $replyTo)
            => (new Mailer())->send($to, $nom, $sujet, $html, $texte, $replyTo);
    }

    /** @param array{email:string,titre:string,description:string} $d données validées */
    public function send(array $d): void
    {
        $destinataire = getenv('CONTACT_TO') ?: Entreprise::get('email');
        $e = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

        $texte = "Nouveau message depuis le formulaire de contact du site.\n\n"
            . "De : {$d['email']}\nObjet : {$d['titre']}\n\n{$d['description']}\n\n---\n"
            . "Pour répondre, répondez simplement à ce message : l'adresse du visiteur est enregistrée comme adresse de réponse.";
        $html = '<p>Nouveau message depuis le formulaire de contact du site.</p>'
            . '<p><strong>De :</strong> ' . $e($d['email']) . '<br><strong>Objet :</strong> ' . $e($d['titre']) . '</p>'
            . '<blockquote>' . nl2br($e($d['description'])) . '</blockquote>'
            . '<p><em>Pour répondre, répondez simplement à ce message : l\'adresse du visiteur est enregistrée comme adresse de réponse.</em></p>';

        ($this->transport)($destinataire, Entreprise::get('nom'), '[Contact du site] ' . $d['titre'], $html, $texte, $d['email']);
    }
}
