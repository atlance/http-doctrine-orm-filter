<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineOrmFilter\Test\Factory;

use Atlance\HttpDoctrineOrmFilter\Query\Configuration;

final class RequestFactory
{
    public static function create(string $uri): Configuration
    {
        parse_str($uri, $args);

        return new Configuration($args);
    }
}
