<?php
declare(strict_types=1);

namespace App\Service;

/** $raison : menu, stock, minimum, delai ou prix. Le message est destiné à l'utilisateur. */
final class OrderException extends \RuntimeException
{
    public function __construct(public readonly string $raison, string $message)
    {
        parent::__construct($message);
    }
}
