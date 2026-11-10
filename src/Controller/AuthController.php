<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Auth;
use App\Core\Http;
use App\Core\Session;
use App\Core\View;
use App\Repository\UserRepository;
use App\Service\AuthService;
use App\Service\Mailer;
use App\Validation\RegistrationValidator;
use PDOException;
use Throwable;

final class AuthController
{
    // ---------- Inscription ----------
    public function showRegister(): void
    {
        // Un visiteur qui vient de la page de commande garde sa destination pendant l'inscription
        $suite = $this->redirectParam($_GET['redirect'] ?? null);
        if ($suite !== null) {
            Session::set('redirect_apres_inscription', $suite);
        } else {
            Session::forget('redirect_apres_inscription');
        }

        View::render('auth/register', ['title' => 'Créer un compte', 'errors' => [], 'old' => []]);
    }

    public function register(): void
    {
        $result = RegistrationValidator::validate($_POST);
        $data = $result['data'];

        if ($result['errors'] !== []) {
            $this->refuseRegister($result['errors'], $data);
            return;
        }

        try {
            (new UserRepository())->createUser($data, password_hash($result['password'], PASSWORD_DEFAULT));
        } catch (PDOException $e) {
            if (($e->errorInfo[1] ?? null) === 1062) { // e-mail déjà utilisé : message volontairement générique
                $this->refuseRegister(['_general' => "Impossible de créer le compte avec ces informations. "
                    . "Si vous avez déjà un compte, connectez-vous."], $data);
                return;
            }
            throw $e;
        }

        $mailOk = true;
        try {
            (new Mailer())->sendWelcome($data['email'], $data['prenom']);
        } catch (Throwable $e) {
            $mailOk = false;
            error_log('Mail de bienvenue non envoyé : ' . $e->getMessage());
        }

        Session::flash('success', $mailOk
            ? 'Votre compte a été créé. Un e-mail de bienvenue vous a été envoyé. Vous pouvez vous connecter.'
            : "Votre compte a été créé. Le e-mail de bienvenue n'a pas pu être envoyé. Vous pouvez vous connecter.");

        $suite = $this->redirectParam(Session::get('redirect_apres_inscription'));
        Session::forget('redirect_apres_inscription');
        Http::redirect('/connexion' . ($suite !== null ? '?redirect=' . rawurlencode($suite) : ''));
    }

    private function refuseRegister(array $errors, array $old): void
    {
        http_response_code(422);
        View::render('auth/register', ['title' => 'Créer un compte', 'errors' => $errors, 'old' => $old]);
    }

    // ---------- Connexion / déconnexion ----------
    public function showLogin(): void
    {
        if (Auth::user() !== null) {
            Http::redirect(Auth::home());
        }
        View::render('auth/login', [
            'title' => 'Connexion',
            'error' => null,
            'old' => [],
            'redirect' => $this->redirectParam($_GET['redirect'] ?? null),
        ]);
    }

    public function login(): void
    {
        $email = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';
        $password = is_string($_POST['mot_de_passe'] ?? null) ? $_POST['mot_de_passe'] : '';
        $redirect = $this->redirectParam($_POST['redirect'] ?? null);

        $user = ($email !== '' && $password !== '') ? (new AuthService())->attempt($email, $password) : null;

        if ($user === null) {
            http_response_code(401);
            View::render('auth/login', [
                'title' => 'Connexion',
                'error' => 'Identifiant ou mot de passe incorrect.',
                'old' => ['email' => $email],
                'redirect' => $redirect,
            ]);
            return;
        }

        Auth::login($user);
        Session::flash('success', 'Vous êtes connecté' . (!empty($user['prenom']) ? ', ' . $user['prenom'] : '') . '.');
        Http::redirect($redirect ?? Auth::home($user));
    }

    public function logout(): void
    {
        Auth::logout();
        Session::flash('success', 'Vous êtes déconnecté.');
        Http::redirect('/');
    }

    private function redirectParam(mixed $value): ?string
    {
        return is_string($value) && strlen($value) <= 500 && Http::isSafePath($value) ? $value : null;
    }
}
