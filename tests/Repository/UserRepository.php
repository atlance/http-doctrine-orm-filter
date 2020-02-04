<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineFilter\Test\Repository;

use Atlance\HttpDoctrineFilter\Filter;
use Atlance\HttpDoctrineFilter\Test\Model\Passport;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Component\Validator\Validation;

class UserRepository extends EntityRepository
{
    /** @var Filter */
    private $filter;

    public function setFilter(Filter $filter): self
    {
        $validator = Validation::createValidatorBuilder()->enableAnnotationMapping()->getValidator();
        $this->filter = $filter->setValidator($validator)->setValidationGroups(['test']);

        return $this;
    }

    public function findByConditions(array $conditions = [])
    {
        $qb = $this->createQueryBuilder('users')
            ->select('COUNT(DISTINCT(users.id))')
            ->leftJoin('users.cards', 'cards', Join::WITH)
            ->leftJoin('users.phones', 'phones', Join::WITH)
            ->leftJoin(Passport::class, 'passport', Join::WITH, 'users.id = passport.user')
        ;

        return $this->filter->setOrmQueryBuilder($qb)
            ->selectBy($conditions['filter'] ?? [])
            ->orderBy($conditions['order'] ?? [])
            ->getOrmQueryBuilder()
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
