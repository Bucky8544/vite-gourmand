<?php
declare(strict_types=1);

namespace App\Validation;

final class ProfileValidator
{
    /** @return array{data: array<string,string>, errors: array<string,string>} */
    public static function validate(array $in): array
    {
        $errors = [];
        $get = static fn (string $k): string => is_string($in[$k] ?? null) ? trim($in[$k]) : '';

        $data = [
            'nom' => $get('nom'),
            'prenom' => $get('prenom'),
            'telephone' => $get('telephone'),
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

        $tel = preg_replace('/[\s.\-]/', '', $data['telephone']) ?? '';
        $tel = preg_replace('/^(\+33|0033)/', '0', $tel) ?? '';
        if ($data['telephone'] === '') {
            $errors['telephone'] = 'Le numéro de GSM est obligatoire.';
        } elseif (!preg_match('/^0[67][0-9]{8}$/', $tel)) {
            $errors['telephone'] = 'Saisissez un numéro de GSM français valide (06 ou 07).';
        } else {
            $data['telephone'] = $tel;
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

        return ['data' => $data, 'errors' => $errors];
    }
}
