<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineFilter\Test\Builder;

use Doctrine\ORM\EntityManager as ORMEntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Setup;

class EntityManagerBuilder
{
    private $em;

    public function __construct()
    {
        $isDevMode = true;
        $proxyDir = null;
        $cache = null;
        $useSimpleAnnotationReader = false;
        $config = Setup::createAnnotationMetadataConfiguration([__DIR__.'/../Model'], $isDevMode, $proxyDir, $cache, $useSimpleAnnotationReader);

        $connect = [
            'driver' => 'pdo_sqlite',
            'path' => __DIR__.'/db.sqlite',
        ];
        $this->em = ORMEntityManager::create($connect, $config);
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->em;
    }
}
