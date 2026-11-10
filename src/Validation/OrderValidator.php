<?php
declare(strict_types=1);

namespace App\Validation;

use DateTimeImmutable;

final class OrderValidator
{
    public const MAX_PERSONNES = 500;

    /**
     * @param array<string,mixed> $in données brutes du formulaire
     * @param array<string,mixed> $menu menu choisi (id, nombre_personne_minimum, delai_commande_jours)
     * @param callable(int):?array $horaires jour ISO (1 = lundi ... 7 = dimanche) -> ['heure_ouverture','heure_fermeture','ferme'] ou null
     * @return array{data: array<string,string|int>, errors: array<string,string>}
     */
    public static function validate(array $in, array $menu, callable $horaires, ?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable('now');
        $aujourdhui = $now->setTime(0, 0);
        $errors = [];
        $get = static fn (string $k): string => is_string($in[$k] ?? null) ? trim($in[$k]) : '';

        $minimum = (int) $menu['nombre_personne_minimum'];
        $delai = (int) ($menu['delai_commande_jours'] ?? 0);

        // ---- Nombre de personnes
        $texte = $get('personnes');
        $personnes = 0;
        if (!preg_match('/^[1-9]\d{0,3}$/', $texte)) {
            $errors['personnes'] = 'Saisissez un nombre de personnes (nombre entier).';
        } else {
            $personnes = (int) $texte;
            if ($personnes < $minimum) {
                $errors['personnes'] = "Ce menu se commande pour $minimum personnes minimum.";
            } elseif ($personnes > self::MAX_PERSONNES) {
                $errors['personnes'] = 'Pour plus de ' . self::MAX_PERSONNES . ' personnes, contactez-nous.';
            }
        }

        // ---- Date de la prestation
        $dateTexte = $get('date_prestation');
        $date = null;
        $analyse = DateTimeImmutable::createFromFormat('!Y-m-d', $dateTexte);
        if ($dateTexte === '') {
            $errors['date_prestation'] = 'La date de la prestation est obligatoire.';
        } elseif ($analyse === false || $analyse->format('Y-m-d') !== $dateTexte) {
            $errors['date_prestation'] = 'Saisissez une date valide.';
        } else {
            $date = $analyse;
            $auPlusTot = $aujourdhui->modify("+$delai days");
            if ($date < $auPlusTot) {
                $errors['date_prestation'] = $delai > 0
                    ? "Ce menu doit être commandé au moins $delai jours avant la prestation : choisissez une date à partir du "
                        . $auPlusTot->format('d/m/Y') . '.'
                    : 'La date de la prestation ne peut pas être dans le passé.';
            } elseif ($date > $aujourdhui->modify('+2 years')) {
                $errors['date_prestation'] = 'Choisissez une date dans les deux ans à venir.';
            }
        }

        // ---- Heure de livraison, selon les horaires de l'entreprise
        $heure = $get('heure_livraison');
        if ($heure === '') {
            $errors['heure_livraison'] = "L'heure de livraison est obligatoire.";
        } elseif (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $heure)) {
            $errors['heure_livraison'] = 'Saisissez une heure valide (par exemple 12:30).';
        } elseif ($date !== null && !isset($errors['date_prestation'])) {
            $jour = $horaires((int) $date->format('N'));
            if ($jour !== null) {
                if (!empty($jour['ferme'])) {
                    $errors['date_prestation'] = 'Nous sommes fermés ce jour-là : choisissez une autre date.';
                } else {
                    $ouverture = substr((string) ($jour['heure_ouverture'] ?? ''), 0, 5);
                    $fermeture = substr((string) ($jour['heure_fermeture'] ?? ''), 0, 5);
                    if ($ouverture !== '' && $fermeture !== '' && ($heure < $ouverture || $heure > $fermeture)) {
                        $errors['heure_livraison'] = "Pour cette date, la livraison est possible entre $ouverture et $fermeture.";
                    }
                }
            }
        }

        // ---- Lieu de la prestation
        $adresse = $get('adresse_prestation');
        $codePostal = $get('code_postal_prestation');
        $ville = $get('ville_prestation');
        if (mb_strlen($adresse) < 5 || mb_strlen($adresse) > 255) {
            $errors['adresse_prestation'] = "L'adresse doit contenir entre 5 et 255 caractères.";
        }
        if (!preg_match('/^[0-9]{5}$/', $codePostal)) {
            $errors['code_postal_prestation'] = 'Le code postal doit contenir 5 chiffres.';
        }
        if (!preg_match('/^[\p{L}][\p{L} \'\-\.]{1,99}$/u', $ville)) {
            $errors['ville_prestation'] = 'Saisissez une ville valide.';
        }

        return [
            'data' => [
                'menu_id' => (int) ($menu['id'] ?? 0),
                'personnes' => $personnes,
                'date_prestation' => $dateTexte,
                'heure_livraison' => $heure,
                'adresse_prestation' => $adresse,
                'code_postal_prestation' => $codePostal,
                'ville_prestation' => $ville,
            ],
            'errors' => $errors,
        ];
    }
}
