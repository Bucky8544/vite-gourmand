<?php
declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $template, array $data = [], string $layout = 'layout'): void
    {
        echo (new self())->capture($template, $data, $layout);
    }

    /** Échappement systématique de toute donnée affichée (protection XSS). */
    public function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
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
