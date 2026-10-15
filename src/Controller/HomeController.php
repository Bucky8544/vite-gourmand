<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\View;

final class HomeController
{
    public function index(): void
    {
        View::render('home', ['title' => 'Accueil', 'current' => '/']);
    }
}
