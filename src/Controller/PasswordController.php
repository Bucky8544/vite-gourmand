<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Http;
use App\Core\Session;
use App\Core\View;
use App\Service\PasswordResetService;
use App\Validation\PasswordPolicy;

final class PasswordController
{
    public function showForgot(): void
    {
        View::render('auth/forgot', ['title' => 'Mot de passe oublié', 'error' => null, 'old' => []]);
    }

    public function forgot(): void
    {
        $email = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';

        // Ce contrôle ne dépend pas de l'existence du compte : il ne révèle rien
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(422);
            View::render('auth/forgot', [
                'title' => 'Mot de passe oublié',
                'error' => 'Saisissez une adresse e-mail valide.',
                'old' => ['email' => $email],
            ]);
            return;
        }

        (new PasswordResetService())->requestReset($email);

        Session::flash('info', 'Si un compte correspond à cette adresse, un e-mail contenant un lien de réinitialisation '
            . 'vient de vous être envoyé. Ce lien est valable ' . PasswordResetService::TTL_MINUTES . ' minutes.');
        Http::redirect('/connexion');
    }

    public function showReset(string $token): void
    {
        $this->sensitiveHeaders();
        if (!(new PasswordResetService())->isValid($token)) {
            $this->invalid();
            return;
        }
        View::render('auth/reset', ['title' => 'Nouveau mot de passe', 'token' => $token, 'errors' => []]);
    }

    public function reset(string $token): void
    {
        $this->sensitiveHeaders();
        $service = new PasswordResetService();
        if (!$service->isValid($token)) {
            $this->invalid();
            return;
        }

        $pw = is_string($_POST['mot_de_passe'] ?? null) ? $_POST['mot_de_passe'] : '';
        $pw2 = is_string($_POST['mot_de_passe_confirmation'] ?? null) ? $_POST['mot_de_passe_confirmation'] : '';

        $errors = [];
        if ($pw === '') {
            $errors['mot_de_passe'] = 'Le mot de passe est obligatoire.';
        } elseif (($manque = PasswordPolicy::check($pw)) !== []) {
            $errors['mot_de_passe'] = 'Le mot de passe doit contenir : ' . implode(', ', $manque) . '.';
        }
        if ($pw !== $pw2) {
            $errors['mot_de_passe_confirmation'] = 'Les deux mots de passe ne sont pas identiques.';
        }

        if ($errors !== []) {
            http_response_code(422);
            View::render('auth/reset', ['title' => 'Nouveau mot de passe', 'token' => $token, 'errors' => $errors]);
            return;
        }

        if (!$service->reset($token, $pw)) { // jeton utilisé ou expiré entre-temps
            $this->invalid();
            return;
        }

        Session::flash('success', 'Votre mot de passe a été modifié. Vous pouvez vous connecter.');
        Http::redirect('/connexion');
    }

    /** Le jeton est dans l'URL : pas de mise en cache, pas de Referer. */
    private function sensitiveHeaders(): void
    {
        header('Cache-Control: no-store');
        header('Referrer-Policy: no-referrer');
    }

    private function invalid(): void
    {
        http_response_code(400);
        View::render('auth/reset-invalid', ['title' => 'Lien invalide ou expiré']);
    }
}
