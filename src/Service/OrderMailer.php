<?php
declare(strict_types=1);

namespace App\Service;

use App\Core\Money;
use DateTimeImmutable;

final class OrderMailer
{
    private Mailer $mailer;

    public function __construct(?Mailer $mailer = null)
    {
        $this->mailer = $mailer ?? new Mailer();
    }

    /** @param array<string,mixed> $c numero, menu_titre, personnes, date_prestation, heure_livraison, adresse_*, devis, distance */
    public function sendConfirmation(string $email, ?string $prenom, array $c): void
    {
        $e = static fn (mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
        $date = (new DateTimeImmutable($c['date_prestation']))->format('d/m/Y');
        $lieu = $c['adresse_prestation'] . ', ' . $c['code_postal_prestation'] . ' ' . $c['ville_prestation'];
        $d = $c['devis'];
        $livraison = $c['distance']['bordeaux']
            ? 'Livraison à Bordeaux : offerte'
            : 'Livraison (' . number_format($c['distance']['km10'] / 10, 1, ',', ' ') . ' km) : ' . Money::format($d['livraison']);
        $remise = $d['remise'] > 0 ? 'Remise de ' . Pricing::REMISE_POURCENT . ' % : -' . Money::format($d['remise']) : null;
        $bonjour = $prenom !== null && $prenom !== '' ? "Bonjour $prenom," : 'Bonjour,';

        $lignes = [
            "Commande n° {$c['numero']}",
            "Menu : {$c['menu_titre']}",
            "Nombre de personnes : {$c['personnes']}",
            "Prestation : le $date à {$c['heure_livraison']}",
            "Lieu : $lieu",
            'Prix du menu : ' . Money::format($d['brut']),
        ];
        if ($remise !== null) {
            $lignes[] = $remise;
        }
        $lignes[] = $livraison;
        $lignes[] = 'Total : ' . Money::format($d['total']);

        $texte = $bonjour . "\n\nMerci pour votre commande chez Vite & Gourmand. Elle est en attente de validation par notre équipe.\n\n"
            . implode("\n", $lignes)
            . "\n\nVous pourrez la modifier ou l'annuler depuis votre espace tant qu'elle n'a pas été acceptée."
            . "\n\nÀ très bientôt,\nJulie et José";

        $html = '<p>' . $e($bonjour) . '</p>'
            . '<p>Merci pour votre commande chez Vite &amp; Gourmand. Elle est en attente de validation par notre équipe.</p><ul>'
            . implode('', array_map(static fn (string $l): string => '<li>' . htmlspecialchars($l, ENT_QUOTES, 'UTF-8') . '</li>', $lignes))
            . '</ul><p>Vous pourrez la modifier ou l\'annuler depuis votre espace tant qu\'elle n\'a pas été acceptée.</p>'
            . '<p>À très bientôt,<br>Julie et José</p>';

        $this->mailer->send($email, (string) $prenom, 'Confirmation de votre commande ' . $c['numero'], $html, $texte);
    }
}
