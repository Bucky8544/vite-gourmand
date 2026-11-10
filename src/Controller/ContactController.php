<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Entreprise;
use App\Core\Http;
use App\Core\Session;
use App\Core\View;
use App\Service\ContactMailer;
use App\Service\ContactThrottle;
use App\Validation\ContactValidator;
use Throwable;

final class ContactController
{
    private const MESSAGE_OK = 'Merci, votre message a bien été envoyé. Nous vous répondrons à l\'adresse indiquée.';

    public function show(): void
    {
        $this->formulaire(['email' => '', 'titre' => '', 'description' => ''], [], 200);
    }

    public function send(): void
    {
        // Piège à robots : un champ invisible que seuls les robots remplissent. On feint le succès sans rien envoyer.
        if (is_string($_POST['site_web'] ?? null) && trim($_POST['site_web']) !== '') {
            Session::flash('success', self::MESSAGE_OK);
            Http::redirect('/contact');
        }

        $v = ContactValidator::validate($_POST);
        $general = [];

        // Un formulaire rempli en moins de 2 secondes depuis l'affichage de la page n'est pas écrit par un humain
        $ouvert = Session::get('contact_ouvert_a');
        if (!is_int($ouvert) || time() - $ouvert < 2) {
            $general['_general'] = 'Le formulaire a été envoyé trop vite. Réessayez dans un instant.';
        }

        if ($v['errors'] !== [] || $general !== []) {
            $this->formulaire($v['old'], $v['errors'] + $general, 422);
            return;
        }

        [$autorise, $envois] = ContactThrottle::evaluer(Session::get('contact_envois'), time());
        if (!$autorise) {
            $this->formulaire($v['old'], ['_general' => 'Vous avez envoyé plusieurs messages récemment. Réessayez dans quelques minutes, '
                . 'ou appelez-nous au ' . Entreprise::get('telephone') . '.'], 429);
            return;
        }

        try {
            (new ContactMailer())->send($v['data']);
        } catch (Throwable $e) {
            error_log('Message de contact non envoyé : ' . $e->getMessage());
            $this->formulaire($v['old'], ['_general' => "Votre message n'a pas pu être envoyé pour le moment. Vos saisies sont conservées : "
                . 'réessayez dans un instant, ou appelez-nous au ' . Entreprise::get('telephone') . '.'], 503);
            return;
        }

        Session::set('contact_envois', $envois);
        Session::flash('success', self::MESSAGE_OK);
        Http::redirect('/contact');
    }

    private function formulaire(array $old, array $errors, int $statut): void
    {
        Session::set('contact_ouvert_a', time()); // début du chronomètre anti-robot
        http_response_code($statut);
        View::render('contact/form', ['title' => 'Contact', 'current' => '/contact', 'old' => $old, 'errors' => $errors]);
    }
}
