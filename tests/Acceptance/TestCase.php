<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineOrmFilter\Test\Acceptance;

use Atlance\HttpDoctrineOrmFilter\Filter;
use Atlance\HttpDoctrineOrmFilter\Query\Configuration;
use Atlance\HttpDoctrineOrmFilter\Test\Builder\EntityManagerBuilder;
use Atlance\HttpDoctrineOrmFilter\Test\Model\Passport;
use Atlance\HttpDoctrineOrmFilter\Test\Model\User;
use Atlance\HttpDoctrineOrmFilter\Test\Repository\UserRepository;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\Psr6\DoctrineProvider;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Validator\Validation;

abstract class TestCase extends BaseTestCase
{
    public function __construct(null | string $name = null, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
    }

    protected function assertCountByHttpQuery(string $uri, int $expectedCount): void
    {
        $filter = $this->createClearFilter();
        $qb = $this->prepareQueryBuilderQuery();

        parse_str($uri, $args);
        $request = new Configuration($args);

        self::assertEquals($expectedCount, $filter->apply($qb, $request)->getQuery()->getSingleScalarResult());
    }

    protected function prepareQueryBuilderQuery(): QueryBuilder
    {
        $filter = $this->createClearFilter();
        $qb = $filter->createQueryBuilder();
        $qb->select('COUNT(DISTINCT(users.id))')
            ->from(User::class, 'users')
            ->leftJoin('users.cards', 'cards', Join::WITH)
            ->leftJoin('users.phones', 'phones', Join::WITH)
            ->leftJoin(Passport::class, 'passport', Join::WITH, 'users.id = passport.user');

        return $qb;
    }

    protected function createClearFilter(): Filter
    {
        $em = (new EntityManagerBuilder())::build();
        $validator = Validation::createValidatorBuilder()->enableAnnotationMapping()->getValidator();
        $cache = $this->createCacheProviderInstance();

        return (new Filter($em, $validator, $cache))->setValidationGroups(['tests']);
    }

    protected function findByConditionUserRepositoryFilter(string $uri): mixed
    {
        parse_str($uri, $args);
        $repository = (
            new UserRepository((new EntityManagerBuilder())::build(), new ClassMetadata(User::class))
        )->setFilter($this->createClearFilter());

        return $repository->findByConditions($args);
    }

    protected function createHttpDoctrineOrmFilterRequest(string $uri): Configuration
    {
        parse_str($uri, $args);

        return new Configuration($args);
    }

    /** @psalm-suppress MixedReturnStatement */
    protected function createCacheProviderInstance(): Cache
    {
        /** @var MemcachedAdapter|RedisAdapter|ApcuAdapter|ArrayAdapter|null $adapter */
        $adapter = null;
        if (\extension_loaded('memcached')) {
            $memcached = new \Memcached();
            $memcached->addServer('127.0.0.1', 11211);

            $adapter = new MemcachedAdapter($memcached);
        }

        if (null === $adapter && \extension_loaded('redis')) {
            $redis = new \Redis();
            $redis->connect('127.0.0.1');

            $adapter = new RedisAdapter($redis);
        }

        if (null === $adapter && \extension_loaded('apcu')) {
            $adapter = new ApcuAdapter();
        }

        if (null === $adapter) {
            $adapter = new ArrayAdapter();
        }

        return DoctrineProvider::wrap($adapter);
    }
}
