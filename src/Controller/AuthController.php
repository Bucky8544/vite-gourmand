<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Http;
use App\Core\Session;
use App\Core\View;
use App\Repository\UserRepository;
use App\Service\Mailer;
use App\Validation\RegistrationValidator;
use PDOException;
use Throwable;

final class AuthController
{
    public function showRegister(): void
    {
        View::render('auth/register', ['title' => 'Créer un compte', 'errors' => [], 'old' => []]);
    }

    public function register(): void
    {
        $result = RegistrationValidator::validate($_POST);
        $data = $result['data'];

        if ($result['errors'] !== []) {
            $this->refuse($result['errors'], $data);
            return;
        }

        try {
            (new UserRepository())->createUser($data, password_hash($result['password'], PASSWORD_DEFAULT));
        } catch (PDOException $e) {
            if (($e->errorInfo[1] ?? null) === 1062) { // e-mail déjà utilisé : message volontairement générique
                $this->refuse(['_general' => "Impossible de créer le compte avec ces informations. "
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
            ? 'Votre compte a été créé. Un e-mail de bienvenue vous a été envoyé.'
            : "Votre compte a été créé. Le e-mail de bienvenue n'a pas pu être envoyé.");
        Http::redirect('/');
    }

    private function refuse(array $errors, array $old): void
    {
        http_response_code(422);
        View::render('auth/register', ['title' => 'Créer un compte', 'errors' => $errors, 'old' => $old]);
    }
}
