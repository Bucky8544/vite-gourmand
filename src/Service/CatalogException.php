<?php
declare(strict_types=1);

namespace App\Service;

/** $raison : introuvable, utilise ou doublon. Le message est destiné à l'utilisateur. */
final class CatalogException extends \RuntimeException
{
    public function __construct(public readonly string $raison, string $message)
    {
        parent::__construct($message);
    }
}
