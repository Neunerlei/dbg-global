<?php
declare(strict_types=1);

if(!is_readable(__DIR__ . '/vendor/autoload.php')) {
    echo 'The dbg-global autoloader is not available.';
    exit(1);
}

if(!isset($GLOBALS['DBG_GLOBAL_AUTOLOADER'])) {
    $GLOBALS['DBG_GLOBAL_AUTOLOADER'] = require __DIR__ . '/vendor/autoload.php';
}

return $GLOBALS['DBG_GLOBAL_AUTOLOADER'];
