<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineOrmFilter\Test\Factory;

use Atlance\HttpDoctrineOrmFilter\Test\Domain\Entity\User\User;
use Atlance\HttpDoctrineOrmFilter\Test\Domain\Entity\User\UserRepository;
use Doctrine\ORM\Mapping\ClassMetadata;

final class UserRepositoryFactory
{
    public static function create(): UserRepository
    {
        return (new UserRepository((new EntityManagerFactory())::create(), new ClassMetadata(User::class)))
            ->setFilter(FilterFactory::create());
    }
}
