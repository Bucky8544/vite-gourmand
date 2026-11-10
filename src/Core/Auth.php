<?php
declare(strict_types=1);

namespace App\Core;

use App\Repository\UserRepository;

final class Auth
{
    private const HOMES = [
        'utilisateur' => '/mon-espace',
        'employe' => '/employe',
        'administrateur' => '/admin',
    ];

    private static bool $loaded = false;
    private static ?array $user = null;

    public static function login(array $user): void
    {
        Session::regenerate(); // nouvel identifiant : anti-fixation de session
        Session::set('user_id', (int) $user['id']);
        unset($user['mot_de_passe']);
        self::$user = $user;
        self::$loaded = true;
    }

    public static function logout(): void
    {
        Session::destroy();
        Session::start();       // nouvelle session vide (nécessaire pour le message flash)
        Session::regenerate();
        self::$user = null;
        self::$loaded = true;
    }

    /** Utilisateur connecté et actif, rechargé depuis la base (un compte désactivé est déconnecté aussitôt). */
    public static function user(): ?array
    {
        if (!self::$loaded) {
            self::$loaded = true;
            $id = Session::get('user_id');
            if (is_int($id)) {
                self::$user = (new UserRepository())->findActiveById($id);
                if (self::$user === null) {
                    Session::forget('user_id');
                }
            }
        }

        return self::$user;
    }

    public static function home(?array $user = null): string
    {
        $user ??= self::user();

        return self::HOMES[$user['role'] ?? ''] ?? '/';
    }

    /** À appeler au début de chaque page protégée. Renvoie l'utilisateur ou interrompt la requête. */
    public static function requireRole(array $roles): array
    {
        $user = self::user();

        if ($user === null) {
            Session::flash('info', 'Veuillez vous connecter pour accéder à cette page.');
            // On garde la page demandée avec ses paramètres (par exemple /commande?menu=3)
            $cible = $_SERVER['REQUEST_URI'] ?? '/';
            $suite = (strlen($cible) <= 500 && Http::isSafePath($cible)) ? '?redirect=' . rawurlencode($cible) : '';
            Http::redirect('/connexion' . $suite);
        }

        if (!in_array($user['role'], $roles, true)) {
            http_response_code(403);
            View::render('errors/403', [
                'title' => 'Accès refusé',
                'message' => "Vous n'avez pas les droits nécessaires pour accéder à cette page.",
            ]);
            exit;
        }

        return $user;
    }
}
