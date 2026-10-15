<?php
declare(strict_types=1);

namespace App\Core;

final class Http
{
    /** Redirection interne uniquement (empêche les redirections vers un site externe). */
    public static function redirect(string $path): never
    {
        if ($path === '' || $path[0] !== '/' || str_starts_with($path, '//')) {
            $path = '/';
        }
        header('Location: ' . $path, true, 303);
        exit;
    }
}
