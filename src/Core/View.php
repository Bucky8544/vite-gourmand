<?php
declare(strict_types=1);

namespace App\Core;

final class View
{
    private const PLACEHOLDER = '/assets/img/placeholder-menu.svg';

    public static function render(string $template, array $data = [], string $layout = 'layout'): void
    {
        echo (new self())->capture($template, $data, $layout);
    }

    /** Échappement systématique de toute donnée affichée (protection XSS). */
    public function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /** 150 -> « 150 € », 112.5 -> « 112,50 € » (espace insécable avant le symbole). */
    public function price(float|int|string $value): string
    {
        $v = (float) $value;
        $texte = abs($v - round($v)) < 0.005
            ? number_format($v, 0, ',', "\u{202F}")
            : number_format($v, 2, ',', "\u{202F}");

        return $texte . "\u{00A0}€";
    }

    /** URL d'image sûre : chemin interne /assets/... existant, sinon image de remplacement. */
    public function menuImage(?string $url): string
    {
        if ($url === null || !preg_match('#^/assets/[A-Za-z0-9/_.-]+$#', $url) || str_contains($url, '..')) {
            return self::PLACEHOLDER;
        }

        return is_file(dirname(__DIR__, 2) . '/public' . $url) ? $url : self::PLACEHOLDER;
    }

    public function partial(string $template, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        require dirname(__DIR__, 2) . '/templates/' . $template . '.php';
    }

    private function capture(string $template, array $data, string $layout): string
    {
        $dir = dirname(__DIR__, 2) . '/templates/';
        extract($data, EXTR_SKIP);

        ob_start();
        require $dir . $template . '.php';
        $content = ob_get_clean();

        ob_start();
        require $dir . $layout . '.php';

        return (string) ob_get_clean();
    }
}
