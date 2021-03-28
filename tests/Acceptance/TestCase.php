<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineOrmFilter\Test\Acceptance;

use Atlance\HttpDoctrineOrmFilter\Filter;
use Atlance\HttpDoctrineOrmFilter\Query\Configuration;
use Atlance\HttpDoctrineOrmFilter\Test\Builder\EntityManagerBuilder;
use Atlance\HttpDoctrineOrmFilter\Test\Model\Passport;
use Atlance\HttpDoctrineOrmFilter\Test\Model\User;
use Atlance\HttpDoctrineOrmFilter\Test\Repository\UserRepository;
use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\MemcachedCache;
use Doctrine\Common\Cache\RedisCache;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Memcached;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Redis;
use Symfony\Component\Validator\Validation;

abstract class TestCase extends BaseTestCase
{
    private Filter $filter;

    public function __construct(null | string $name = null, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->filter = $this->createClearFilter();
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
        $cacheProvider = $this->createCacheProviderInstance();

        return (new Filter($em, $validator, $cacheProvider))->setValidationGroups(['tests']);
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

    protected function createCacheProviderInstance(): CacheProvider
    {
        if (\extension_loaded('memcached')) {
            $memcached = new Memcached();
            $memcached->addServer('127.0.0.1', 11211);

            $cacheProvider = new MemcachedCache();
            $cacheProvider->setMemcached($memcached);

            return $cacheProvider;
        }

        if (\extension_loaded('redis')) {
            $redis = new Redis();
            $redis->connect('127.0.0.1');

            $cacheProvider = new RedisCache();
            $cacheProvider->setRedis($redis);

            return $cacheProvider;
        }

        if (\extension_loaded('apcu')) {
            return new ApcuCache();
        }

        return new ArrayCache();
    }
}
