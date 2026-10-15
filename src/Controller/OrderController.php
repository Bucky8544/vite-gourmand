<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Auth;
use App\Core\Http;
use App\Core\View;
use App\Repository\MenuRepository;

/** Page de commande provisoire : le formulaire complet arrive avec la carte « Commande d'un menu ». */
final class OrderController
{
    public function create(): void
    {
        $user = Auth::requireRole(['utilisateur']);

        $id = is_string($_GET['menu'] ?? null) ? $_GET['menu'] : '';
        if (!preg_match('/^[1-9]\d{0,9}$/', $id)) {
            Http::notFound();
        }
        $menu = (new MenuRepository())->find((int) $id);
        if ($menu === null) {
            Http::notFound();
        }

        View::render('orders/new', ['title' => 'Commander', 'user' => $user, 'menu' => $menu]);
    }
}
