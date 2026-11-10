<?php
declare(strict_types=1);

namespace App\Validation;

/** Création d'un compte employé : e-mail (identifiant) et mot de passe. Aucun champ « rôle » n'est lu. */
final class EmployeeValidator
{
    /** @return array{data: array{email:string,password:string}, errors: array<string,string>} */
    public static function validate(array $in): array
    {
        $errors = [];
        $email = mb_strtolower(trim(is_string($in['email'] ?? null) ? $in['email'] : ''));
        $mdp = is_string($in['mot_de_passe'] ?? null) ? $in['mot_de_passe'] : '';
        $confirmation = is_string($in['mot_de_passe_confirmation'] ?? null) ? $in['mot_de_passe_confirmation'] : '';

        if ($email === '') {
            $errors['email'] = "L'adresse e-mail est obligatoire.";
        } elseif (mb_strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Saisissez une adresse e-mail valide.';
        }

        if ($mdp === '') {
            $errors['mot_de_passe'] = 'Le mot de passe est obligatoire.';
        } elseif (($manque = PasswordPolicy::check($mdp)) !== []) {
            $errors['mot_de_passe'] = 'Le mot de passe doit contenir : ' . implode(', ', $manque) . '.';
        }
        if ($mdp !== $confirmation) {
            $errors['mot_de_passe_confirmation'] = 'Les deux mots de passe ne sont pas identiques.';
        }

        return ['data' => ['email' => $email, 'password' => $mdp], 'errors' => $errors];
    }
}
