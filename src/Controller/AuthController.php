<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Auth;
use App\Core\ClientIp;
use App\Core\Http;
use App\Core\Session;
use App\Core\View;
use App\Repository\UserRepository;
use App\Service\AuthService;
use App\Service\LoginThrottle;
use App\Service\Mailer;
use App\Service\SignupThrottle;
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

        // Limite les comptes créés depuis une même adresse (chaque inscription envoie un e-mail)
        $ip = ClientIp::courante();
        $inscriptions = null;
        $attente = null;
        try {
            $inscriptions = new SignupThrottle();
            $attente = $inscriptions->attente($ip);
        } catch (Throwable $e) {
            error_log('Limitation des inscriptions indisponible : ' . $e->getMessage());
            $inscriptions = null;
        }
        if ($attente !== null) {
            header('Retry-After: ' . $attente);
            $this->refuseRegister(['_general' => 'Trop de comptes ont été créés depuis votre connexion. Réessayez dans '
                . $this->duree($attente) . '.'], $data, 429);
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

        if ($inscriptions !== null) {
            try {
                $inscriptions->enregistrer($ip);
            } catch (Throwable $e) {
                error_log('Limitation des inscriptions : enregistrement impossible : ' . $e->getMessage());
            }
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

    private function refuseRegister(array $errors, array $old, int $statut = 422): void
    {
        http_response_code($statut);
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

        $ip = ClientIp::courante();
        $limite = null;
        $attente = null;
        try {
            $limite = new LoginThrottle();
            $attente = $limite->attente($email, $ip);
        } catch (Throwable $e) {
            error_log('Limitation des connexions indisponible : ' . $e->getMessage());
            $limite = null;
        }

        if ($attente !== null) {
            // Trop d'échecs récents : on ne vérifie même pas le mot de passe, une bonne réponse ne doit rien révéler
            http_response_code(429);
            header('Retry-After: ' . $attente);
            View::render('auth/login', [
                'title' => 'Connexion',
                'error' => 'Trop de tentatives de connexion. Réessayez dans ' . $this->duree($attente) . '.',
                'old' => ['email' => $email],
                'redirect' => $redirect,
            ]);
            return;
        }

        $user = ($email !== '' && $password !== '') ? (new AuthService())->attempt($email, $password) : null;

        if ($user === null) {
            if ($limite !== null && $email !== '' && $password !== '') {
                try {
                    $limite->echec($email, $ip);
                } catch (Throwable $e) {
                    error_log('Limitation des connexions : enregistrement impossible : ' . $e->getMessage());
                }
            }
            http_response_code(401);
            View::render('auth/login', [
                'title' => 'Connexion',
                'error' => 'Identifiant ou mot de passe incorrect.',
                'old' => ['email' => $email],
                'redirect' => $redirect,
            ]);
            return;
        }

        if ($limite !== null) {
            try {
                $limite->succes($email, $ip);
            } catch (Throwable $e) {
                error_log('Limitation des connexions : remise à zéro impossible : ' . $e->getMessage());
            }
        }

        Auth::login($user);
        Session::flash('success', 'Bienvenue' . (!empty($user['prenom']) ? ' ' . $user['prenom'] : '') . ' !');
        // Un client arrive sur la page d'accueil (ou retourne là où il voulait aller) ; l'équipe et l'administrateur arrivent dans leur espace
        $destination = ($user['role'] ?? '') === 'utilisateur' ? '/' : Auth::home($user);
        Http::redirect($redirect ?? $destination);
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

    /** « 1 minute », « 14 minutes »… */
    private function duree(int $secondes): string
    {
        $minutes = max(1, (int) ceil($secondes / 60));

        return $minutes . ' minute' . ($minutes > 1 ? 's' : '');
    }
}
