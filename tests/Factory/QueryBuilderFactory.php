<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineOrmFilter\Test\Factory;

use Doctrine\ORM\QueryBuilder;

final class QueryBuilderFactory
{
    public static function create(): QueryBuilder
    {
        return new QueryBuilder((new EntityManagerFactory())::create());
    }
}
