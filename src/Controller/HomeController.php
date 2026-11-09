<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\View;
use App\Repository\ReviewRepository;

final class HomeController
{
    public function index(): void
    {
        $avis = new ReviewRepository();

        View::render('home', [
            'title' => 'Traiteur à Bordeaux',
            'current' => '/',
            'avis' => $avis->validatedForHome(6),
            'statsAvis' => $avis->statsValidated(),
        ]);
    }
}
