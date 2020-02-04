<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineOrmFilter\Test\Acceptance\Filter;

use Atlance\HttpDoctrineOrmFilter\Test\Domain\Entity\User\UserRepository;
use Atlance\HttpDoctrineOrmFilter\Test\Factory\InvalidArgumentExceptionFactory;
use Atlance\HttpDoctrineOrmFilter\Test\Factory\RequestFactory;
use Atlance\HttpDoctrineOrmFilter\Test\Factory\UserRepositoryFactory;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class AbstractTestCase extends BaseTestCase
{
    protected ?UserRepository $repository = null;
    private static UserRepository $staticRepository;

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

        self::$staticRepository = UserRepositoryFactory::create();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = self::$staticRepository;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->repository = null;
    }
}
