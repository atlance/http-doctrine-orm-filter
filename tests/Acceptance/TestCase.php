<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineFilter\Test\Acceptance;

use Atlance\HttpDoctrineFilter\Dto\QueryConfiguration;
use Atlance\HttpDoctrineFilter\Filter;
use Atlance\HttpDoctrineFilter\Test\Builder\EntityManagerBuilder;
use Atlance\HttpDoctrineFilter\Test\Model\Passport;
use Atlance\HttpDoctrineFilter\Test\Model\User;
use Atlance\HttpDoctrineFilter\Test\Repository\UserRepository;
use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\MemcachedCache;
use Doctrine\Common\Cache\RedisCache;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Memcached;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Redis;
use Symfony\Component\Validator\Validation;

abstract class TestCase extends BaseTestCase
{
    private $filter;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->filter = $this->createClearFilter();
    }

    protected function assertCountByHttpQuery(string $uri, int $expectedCount)
    {
        $filter = $this->createClearFilter();
        $qb = $this->prepareQueryBuilderQuery();

        parse_str($uri, $args);
        $request = new QueryConfiguration($args);

        $this->assertEquals($expectedCount, $filter->apply($qb, $request)->getSingleScalarResult());
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
        $em = (new EntityManagerBuilder())->getEntityManager();
        $validator = Validation::createValidatorBuilder()->enableAnnotationMapping()->getValidator();
        $cacheProvider = $this->createCacheProviderInstance();

        return (new Filter($em, $validator, $cacheProvider))->setValidationGroups(['tests']);
    }

    protected function findByConditionUserRepositoryFilter(string $uri)
    {
        parse_str($uri, $args);

        /** @var UserRepository $userRepository */
        $userRepository = (new EntityManagerBuilder())
            ->getEntityManager()
            ->getRepository(User::class)
            ->setFilter($this->createClearFilter()); // without auto wiring =(

        return $userRepository->findByConditions($args);
    }

    protected function createHttpDoctrineFilterRequest(string $uri)
    {
        parse_str($uri, $args);
        return new QueryConfiguration($args);
    }

    protected function createCacheProviderInstance(): CacheProvider
    {
        if (extension_loaded('memcached')) {
            $memcached = new Memcached();
            $memcached->addServer('127.0.0.1', 11211);

            $cacheProvider = new MemcachedCache();
            $cacheProvider->setMemcached($memcached);

            return $cacheProvider;
        }

        if (extension_loaded('redis')) {
            $redis = new Redis();
            $redis->connect('127.0.0.1');

            $cacheProvider = new RedisCache();
            $cacheProvider->setRedis($redis);

            return $cacheProvider;
        }

        if (extension_loaded('apcu')) {
            return new ApcuCache();
        }

        return new ArrayCache();
    }
}
