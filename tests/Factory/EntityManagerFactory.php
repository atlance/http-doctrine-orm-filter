<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineOrmFilter\Test\Factory;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;

final class EntityManagerFactory
{
    public static function create(): EntityManagerInterface
    {
        return new EntityManager(
            DriverManager::getConnection(['driver' => 'pdo_sqlite', 'path' => __DIR__ . '/../../storage/db.sqlite']),
            ORMSetup::createAttributeMetadataConfiguration([__DIR__ . '/../../tests/Domain'], true)
        );
    }
}
