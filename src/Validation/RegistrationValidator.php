<?php
declare(strict_types=1);

namespace App\Validation;

final class RegistrationValidator
{
    /**
     * @param array<string,mixed> $in données brutes du formulaire
     * @return array{data: array<string,string>, password: string, errors: array<string,string>}
     */
    public static function validate(array $in): array
    {
        $errors = [];
        $get = static fn (string $k): string => is_string($in[$k] ?? null) ? trim($in[$k]) : '';

        $data = [
            'nom' => $get('nom'),
            'prenom' => $get('prenom'),
            'telephone' => $get('telephone'),
            'email' => mb_strtolower($get('email')),
            'adresse_postale' => $get('adresse_postale'),
            'code_postal' => $get('code_postal'),
            'ville' => $get('ville'),
        ];

        foreach (['nom' => 'Le nom', 'prenom' => 'Le prénom'] as $k => $label) {
            if ($data[$k] === '') {
                $errors[$k] = "$label est obligatoire.";
            } elseif (!preg_match('/^[\p{L}][\p{L} \'\-]{1,99}$/u', $data[$k])) {
                $errors[$k] = "$label doit contenir 2 à 100 lettres (espaces, apostrophes et tirets autorisés).";
            }
        }

        // GSM français : 06 ou 07, avec ou sans séparateurs, préfixe +33 accepté
        $tel = preg_replace('/[\s.\-]/', '', $data['telephone']) ?? '';
        $tel = preg_replace('/^(\+33|0033)/', '0', $tel) ?? '';
        if ($data['telephone'] === '') {
            $errors['telephone'] = 'Le numéro de GSM est obligatoire.';
        } elseif (!preg_match('/^0[67][0-9]{8}$/', $tel)) {
            $errors['telephone'] = 'Saisissez un numéro de GSM français valide (06 ou 07).';
        } else {
            $data['telephone'] = $tel;
        }

        if ($data['email'] === '') {
            $errors['email'] = "L'adresse e-mail est obligatoire.";
        } elseif (mb_strlen($data['email']) > 190 || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "Saisissez une adresse e-mail valide.";
        }

        if (mb_strlen($data['adresse_postale']) < 5 || mb_strlen($data['adresse_postale']) > 255) {
            $errors['adresse_postale'] = "L'adresse doit contenir entre 5 et 255 caractères.";
        }
        if (!preg_match('/^[0-9]{5}$/', $data['code_postal'])) {
            $errors['code_postal'] = 'Le code postal doit contenir 5 chiffres.';
        }
        if (!preg_match('/^[\p{L}][\p{L} \'\-\.]{1,99}$/u', $data['ville'])) {
            $errors['ville'] = 'Saisissez une ville valide.';
        }

        // Pas de trim sur les mots de passe : les espaces en font partie
        $pw = is_string($in['mot_de_passe'] ?? null) ? $in['mot_de_passe'] : '';
        $pw2 = is_string($in['mot_de_passe_confirmation'] ?? null) ? $in['mot_de_passe_confirmation'] : '';
        if ($pw === '') {
            $errors['mot_de_passe'] = 'Le mot de passe est obligatoire.';
        } elseif (($manque = PasswordPolicy::check($pw)) !== []) {
            $errors['mot_de_passe'] = 'Le mot de passe doit contenir : ' . implode(', ', $manque) . '.';
        }
        if ($pw !== $pw2) {
            $errors['mot_de_passe_confirmation'] = 'Les deux mots de passe ne sont pas identiques.';
        }

        if (($in['consentement'] ?? '') !== '1') {
            $errors['consentement'] = 'Vous devez accepter la politique de confidentialité pour créer un compte.';
        }

        return ['data' => $data, 'password' => $pw, 'errors' => $errors];
    }
}
