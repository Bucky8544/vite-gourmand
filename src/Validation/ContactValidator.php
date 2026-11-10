<?php
declare(strict_types=1);

namespace App\Validation;

final class ContactValidator
{
    /** @return array{data: array{email:string,titre:string,description:string}, errors: array<string,string>, old: array<string,string>} */
    public static function validate(array $in): array
    {
        $errors = [];
        $get = static fn (string $k): string => is_string($in[$k] ?? null) ? trim($in[$k]) : '';

        $email = mb_strtolower($get('email'));
        $titre = $get('titre');
        $description = str_replace(["\r\n", "\r"], "\n", $get('description'));

        if ($email === '') {
            $errors['email'] = "Votre adresse e-mail est obligatoire : c'est à cette adresse que nous vous répondrons.";
        } elseif (mb_strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Saisissez une adresse e-mail valide.';
        }

        // Pas de saut de ligne ni de caractère de contrôle dans l'objet : il finit dans un en-tête de mail
        if (preg_match('/[\x00-\x1F\x7F]/', $titre)) {
            $errors['titre'] = "L'objet ne doit pas contenir de saut de ligne.";
        } elseif (mb_strlen($titre) < 3 || mb_strlen($titre) > 150) {
            $errors['titre'] = "L'objet doit contenir entre 3 et 150 caractères.";
        }

        if (preg_match('/\x00/', $description)) {
            $errors['description'] = 'Le message contient un caractère non autorisé.';
        } elseif (mb_strlen($description) < 10 || mb_strlen($description) > 3000) {
            $errors['description'] = 'Le message doit contenir entre 10 et 3000 caractères.';
        }

        return [
            'data' => ['email' => $email, 'titre' => $titre, 'description' => $description],
            'errors' => $errors,
            'old' => ['email' => $email, 'titre' => mb_substr($titre, 0, 200), 'description' => mb_substr($description, 0, 3001)],
        ];
    }
}
