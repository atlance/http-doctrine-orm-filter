<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineOrmFilter\Test\Factory;

use Atlance\HttpDoctrineOrmFilter\Filter;
use Atlance\HttpDoctrineOrmFilter\Test\Utils\Cache\SimpleCacheBridge;

final class FilterFactory
{
    public static function create(): Filter
    {
        return new Filter(
            ValidatorFactory::create(),
            (null !== $adapter = CacheFactory::create()) ? new SimpleCacheBridge($adapter) : null
        );
    }
}
