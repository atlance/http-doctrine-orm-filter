<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineOrmFilter\Test\Acceptance\Filter;

use Atlance\HttpDoctrineOrmFilter\Query\Configuration;
use Atlance\HttpDoctrineOrmFilter\Test\Domain\Entity\User\UserRepository;
use Atlance\HttpDoctrineOrmFilter\Test\Factory\InvalidArgumentExceptionFactory;
use Atlance\HttpDoctrineOrmFilter\Test\Factory\RequestFactory;
use Atlance\HttpDoctrineOrmFilter\Test\Factory\UserRepositoryFactory;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use InvalidArgumentException;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\SimpleCache\InvalidArgumentException as PsrException;
use Symfony\Component\Validator\Exception\ValidatorException;

abstract class AbstractTestCase extends BaseTestCase
{
    public const EQ = 'filter[eq]';
    public const NEQ = 'filter[neq]';
    public const GT = 'filter[gt]';
    public const GTE = 'filter[gte]';
    public const ILIKE = 'filter[ilike]';
    public const IN = 'filter[in]';
    public const NOT_IN = 'filter[not_in]';
    public const IS_NULL = 'filter[is_null]';
    public const IS_NOT_NULL = 'filter[is_not_null]';
    public const LIKE = 'filter[like]';
    public const NOT_LIKE = 'filter[not_like]';
    public const LT = 'filter[lt]';
    public const LTE = 'filter[lte]';
    public const BETWEEN = 'filter[between]';
    private ?UserRepository $repository;

    private static UserRepository $staticRepository;

    /**
     * @throws InvalidArgumentException if the query arguments logic exception
     * @throws ValidatorException       if not valid the query arguments value
     * @throws PsrException             if cache problem
     * @throws NoResultException        if the query returned no result
     * @throws NonUniqueResultException if the query result is not unique
     */
    public function fetch(string $uri): mixed
    {
        return $this->repository()->fetch(RequestFactory::create($uri));
    }

    /**
     * @throws InvalidArgumentException if the query arguments logic exception
     * @throws ValidatorException       if not valid the query arguments value
     * @throws PsrException             if cache problem
     */
    public function buildOrmQuery(Configuration $conditions): QueryBuilder
    {
        return $this->repository()->buildQuery($conditions);
    }

    /**
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException   if the query arguments logic exception
     * @throws ValidatorException         if not valid the query arguments value
     * @throws PsrException               if cache problem
     * @throws NoResultException          if the query returned no result
     * @throws NonUniqueResultException   if the query result is not unique
     */
    protected function assertCountByHttpQuery(string $uri, int $expectedCount): void
    {
        self::assertEquals($expectedCount, $this->fetch($uri));
    }

    private function repository(): UserRepository
    {
        if (null === $this->repository) {
            throw InvalidArgumentExceptionFactory::create(UserRepository::class);
        }

        return $this->repository;
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
