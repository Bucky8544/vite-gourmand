<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Auth;
use App\Core\View;

/** Espaces provisoires : ils seront remplacés par les vraies pages, mais la protection par rôle restera. */
final class SpaceController
{
    public function utilisateur(): void
    {
        $user = Auth::requireRole(['utilisateur']);
        View::render('space/utilisateur', ['title' => 'Mon espace', 'user' => $user, 'current' => '/mon-espace']);
    }

    public function employe(): void
    {
        $user = Auth::requireRole(['employe', 'administrateur']);
        View::render('space/employe', ['title' => 'Espace employé', 'user' => $user, 'current' => '/employe']);
    }

    public function admin(): void
    {
        $user = Auth::requireRole(['administrateur']);
        View::render('space/admin', ['title' => 'Espace administrateur', 'user' => $user, 'current' => '/admin']);
    }
}
