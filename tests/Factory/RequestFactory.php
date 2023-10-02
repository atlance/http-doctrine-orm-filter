<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineOrmFilter\Test\Factory;

use Atlance\HttpDoctrineOrmFilter\Query\Configuration;
use Atlance\HttpDoctrineOrmFilter\Utils\JsonNormalizer;

final class RequestFactory
{
    public static function create(string $uri): Configuration
    {
        parse_str($uri, $args);
        /** @var array<string,mixed> $args */
        return Configuration::fromArray(JsonNormalizer::normalize($args));
    }
}
