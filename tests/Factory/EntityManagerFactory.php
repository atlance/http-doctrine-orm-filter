<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineOrmFilter\Test\Factory;

use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Setup;

final class EntityManagerFactory
{
    private array $metadataPth = [__DIR__ . '/../../tests/Model'];
    private bool $isDevMode = true;
    private ?string $proxyDir = null;
    private ?Cache $cache = null;
    private bool $useSimpleAnnotationReader = false;
    private static array $staticConnection;
    private static Configuration $staticConfig;

    public function __construct()
    {
        self::$staticConfig = Setup::createAnnotationMetadataConfiguration(
            $this->metadataPth,
            $this->isDevMode,
            $this->proxyDir,
            $this->cache,
            $this->useSimpleAnnotationReader
        );

        self::$staticConnection = [
            'driver' => 'pdo_sqlite',
            'path' => __DIR__ . '/../../storage/db.sqlite',
        ];
    }

    public static function create(array $connection = null, Configuration $config = null): EntityManagerInterface
    {
        return EntityManager::create($connection ?? self::$staticConnection, $config ?? self::$staticConfig);
    }
}
