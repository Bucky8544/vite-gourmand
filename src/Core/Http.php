<?php
declare(strict_types=1);

namespace App\Core;

final class Http
{
    /** Chemin interne uniquement : refuse //site, /\site, http://site et les caractères de contrôle. */
    public static function isSafePath(string $path): bool
    {
        return $path !== ''
            && $path[0] === '/'
            && !str_starts_with($path, '//')
            && !str_contains($path, '\\')
            && !preg_match('/[\x00-\x1F\x7F]/', $path);
    }

    public static function redirect(string $path): never
    {
        if (!self::isSafePath($path)) {
            $path = '/';
        }
        header('Location: ' . $path, true, 303);
        exit;
    }
}
