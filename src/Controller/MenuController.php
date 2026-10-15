<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\View;
use App\Repository\MenuRepository;
use App\Validation\MenuFilter;

final class MenuController
{
    private const JSON_FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR;

    public function index(): void
    {
        $parsed = MenuFilter::parse($_GET);
        $repo = new MenuRepository();

        // Valeurs brutes pour pré-remplir le formulaire (chaînes uniquement)
        $old = [];
        foreach (MenuFilter::FIELDS as $champ) {
            $old[$champ] = is_string($_GET[$champ] ?? null) ? mb_substr(trim($_GET[$champ]), 0, 20) : '';
        }

        View::render('menus/index', [
            'title' => 'Nos menus',
            'current' => '/menus',
            'menus' => $repo->search($parsed['filters']),
            'themes' => $repo->themes(),
            'regimes' => $repo->regimes(),
            'filters' => $parsed['filters'],
            'errors' => $parsed['errors'],
            'old' => $old,
        ]);
    }

    /** Données publiques uniquement. Utilisé par le JavaScript des filtres dynamiques. */
    public function api(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $parsed = MenuFilter::parse($_GET);
        if ($parsed['errors'] !== []) {
            http_response_code(422);
            echo json_encode(['errors' => $parsed['errors']], self::JSON_FLAGS);
            return;
        }

        $view = new View();
        $menus = array_map(static fn (array $m): array => [
            'id' => (int) $m['id'],
            'titre' => $m['titre'],
            'description' => $m['description'],
            'theme' => $m['theme'],
            'regime' => $m['regime'],
            'nombre_personne_minimum' => (int) $m['nombre_personne_minimum'],
            'prix_minimum' => (float) $m['prix_minimum'],
            'stock' => (int) $m['stock'],
            'image' => ['url' => $view->menuImage($m['image_url']), 'alt' => (string) ($m['image_alt'] ?? '')],
        ], (new MenuRepository())->search($parsed['filters']));

        echo json_encode(['count' => count($menus), 'menus' => $menus], self::JSON_FLAGS);
    }
}
