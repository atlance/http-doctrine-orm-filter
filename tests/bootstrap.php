<?php

declare(strict_types=1);

use Doctrine\Common\Annotations\AnnotationRegistry;

call_user_func(function () {
    if (!is_file(__DIR__.'/../vendor/autoload.php')) {
        throw new \RuntimeException('Did not find vendor/autoload.php. Did you run "composer install --dev"?');
    }

    $loader = require __DIR__.'/../vendor/autoload.php';
    AnnotationRegistry::registerLoader([$loader, 'loadClass']);
});
