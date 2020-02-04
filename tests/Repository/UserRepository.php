<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineOrmFilter\Test\Repository;

use Atlance\HttpDoctrineOrmFilter\Filter;
use Atlance\HttpDoctrineOrmFilter\Query\Configuration;
use Atlance\HttpDoctrineOrmFilter\Test\Model\Passport;
use Atlance\HttpDoctrineOrmFilter\Test\Model\User;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

class UserRepository extends EntityRepository
{
    /** @var Filter */
    private $filter;

    public function setFilter(Filter $filter): self
    {
        $this->filter = $filter->setValidationGroups(['test']);

        return $this;
    }

    public function findByConditions(array $conditions = []): mixed
    {
        $qb = $this->filter->createQueryBuilder()
            ->select('COUNT(DISTINCT(users.id))')
            ->from(User::class, 'users')
            ->leftJoin('users.cards', 'cards', Join::WITH)
            ->leftJoin('users.phones', 'phones', Join::WITH)
            ->leftJoin(Passport::class, 'passport', Join::WITH, 'users.id = passport.user')
        ;

        return $this->filter->apply($qb, new Configuration($conditions))->getQuery()->getSingleScalarResult();
    }
}
