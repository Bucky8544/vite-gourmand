<?php
declare(strict_types=1);

// Chargé automatiquement par Composer (autoload « files ») : fuseau horaire et réglages d'hébergement.
date_default_timezone_set('Europe/Paris');

if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg') {
    \App\Core\Deployment::appliquer($_SERVER);
}
