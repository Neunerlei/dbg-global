<?php
declare(strict_types=1);

if(!is_readable(__DIR__ . '/vendor/autoload.php')) {
    echo 'The dbg-global autoloader is not available.';
    exit(1);
}

static $autoLoader = null;
if($autoLoader === null) {
    $autoLoader = require __DIR__ . '/vendor/autoload.php';
}

return $autoLoader;
