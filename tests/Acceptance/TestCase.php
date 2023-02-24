<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineOrmFilter\Test\Acceptance;

use Atlance\HttpDoctrineOrmFilter\Filter;
use Atlance\HttpDoctrineOrmFilter\Test\Factory\CacheFactory;
use Atlance\HttpDoctrineOrmFilter\Test\Factory\EntityManagerFactory;
use Atlance\HttpDoctrineOrmFilter\Test\Factory\InvalidArgumentExceptionFactory;
use Atlance\HttpDoctrineOrmFilter\Test\Factory\RequestFactory;
use Atlance\HttpDoctrineOrmFilter\Test\Factory\ValidatorFactory;
use Atlance\HttpDoctrineOrmFilter\Test\Model\User;
use Atlance\HttpDoctrineOrmFilter\Test\Repository\UserRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected ?UserRepository $repository = null;

    private static UserRepository $staticRepository;

    public function __construct(null | string $name = null, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
    }

    public function repository(): UserRepository
    {
        if (null === $this->repository) {
            throw InvalidArgumentExceptionFactory::create(UserRepository::class);
        }

        return $this->repository;
    }

    protected function fetch(string $uri): mixed
    {
        return $this->repository()->findByConditions(RequestFactory::create($uri));
    }

    protected function assertCountByHttpQuery(string $uri, int $expectedCount): void
    {
        self::assertEquals($expectedCount, $this->fetch($uri));
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $repository = new UserRepository((new EntityManagerFactory())::create(), new ClassMetadata(User::class));
        $repository->setFilter(new Filter(ValidatorFactory::create(), CacheFactory::create()));

        self::$staticRepository = $repository;
    }

    protected function setUp(): void
    {
        $this->repository = self::$staticRepository;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->repository = null;
    }
}
