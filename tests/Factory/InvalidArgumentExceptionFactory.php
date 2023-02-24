<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineOrmFilter\Test\Factory;

final class InvalidArgumentExceptionFactory
{
    public static function create(string $expected): \InvalidArgumentException
    {
        return new \InvalidArgumentException(sprintf('expected %s', $expected));
    }
}
