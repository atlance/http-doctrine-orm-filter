<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineOrmFilter\Test\Repository;

use Atlance\HttpDoctrineOrmFilter\Filter;
use Atlance\HttpDoctrineOrmFilter\Query\Configuration;
use Atlance\HttpDoctrineOrmFilter\Test\Factory\InvalidArgumentExceptionFactory;
use Atlance\HttpDoctrineOrmFilter\Test\Model\Passport;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

class UserRepository extends EntityRepository
{
    private ?Filter $filter = null;

    public function setFilter(Filter $filter): self
    {
        $this->filter = $filter;

        return $this;
    }

    public function filter(): Filter
    {
        if (null === $this->filter) {
            throw InvalidArgumentExceptionFactory::create(Filter::class);
        }

        return $this->filter;
    }

    public function findByConditions(Configuration $conditions): mixed
    {
        return $this->builderByConditions($conditions)->getQuery()->getSingleScalarResult();
    }

    public function builderByConditions(Configuration $conditions): QueryBuilder
    {
        $qb = $this->createQueryBuilder('users')
            ->select('COUNT(DISTINCT(users.id))')
            ->leftJoin('users.cards', 'cards', Join::WITH)
            ->leftJoin('users.phones', 'phones', Join::WITH)
            ->leftJoin(Passport::class, 'passport', Join::WITH, 'users.id = passport.user')
        ;

        $this->filter()->apply($qb, $conditions);

        return $qb;
    }
}
