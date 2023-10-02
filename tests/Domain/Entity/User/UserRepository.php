<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineOrmFilter\Test\Domain\Entity\User;

use Atlance\HttpDoctrineOrmFilter\Filter;
use Atlance\HttpDoctrineOrmFilter\Query\Configuration;
use Atlance\HttpDoctrineOrmFilter\Test\Domain\Entity\Passport\Passport;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Psr\SimpleCache\InvalidArgumentException as PsrException;
use Symfony\Component\Validator\Exception\ValidatorException;
use InvalidArgumentException;

/**
 * @extends EntityRepository<User>
 */
final class UserRepository extends EntityRepository
{
    public function __construct(EntityManagerInterface $em, private readonly Filter $filter)
    {
        parent::__construct($em, new ClassMetadata(User::class));
    }

    /**
     * @throws InvalidArgumentException if the query arguments logic exception
     * @throws ValidatorException       if not valid the query arguments value
     * @throws PsrException             if cache problem
     * @throws NoResultException        If the query returned no result.
     * @throws NonUniqueResultException If the query result is not unique.
     */
    public function fetch(Configuration $conditions): mixed
    {
        return $this->buildQuery($conditions)->getQuery()->getSingleScalarResult();
    }

    /**
     * @throws InvalidArgumentException if the query arguments logic exception
     * @throws ValidatorException       if not valid the query arguments value
     * @throws PsrException             if cache problem
     */
    public function buildQuery(Configuration $conditions): QueryBuilder
    {
        $qb = $this->createQueryBuilder('users')
            ->select('COUNT(DISTINCT(users.id))')
            ->leftJoin('users.cards', 'cards', Join::WITH)
            ->leftJoin('users.phones', 'phones', Join::WITH)
            ->leftJoin(Passport::class, 'passport', Join::WITH, 'users.id = passport.user')
        ;

        $this->filter->apply($qb, $conditions);

        return $qb;
    }
}
