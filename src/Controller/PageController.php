<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\View;

/** Pages d'information : mentions légales, CGV, politique de confidentialité, accessibilité. */
final class PageController
{
    public function mentions(): void
    {
        View::render('legal/mentions', ['title' => 'Mentions légales']);
    }

    public function cgv(): void
    {
        View::render('legal/cgv', ['title' => 'Conditions générales de vente']);
    }

    public function confidentialite(): void
    {
        View::render('legal/confidentialite', ['title' => 'Politique de confidentialité']);
    }

    public function accessibilite(): void
    {
        View::render('legal/accessibilite', ['title' => 'Accessibilité']);
    }
}
