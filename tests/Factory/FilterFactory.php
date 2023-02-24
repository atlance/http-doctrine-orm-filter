<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineOrmFilter\Test\Factory;

use Atlance\HttpDoctrineOrmFilter\Filter;

final class FilterFactory
{
    public static function create(): Filter
    {
        return (new Filter(ValidatorFactory::create(), CacheFactory::create()))->setValidationGroups(['tests']);
    }
}
