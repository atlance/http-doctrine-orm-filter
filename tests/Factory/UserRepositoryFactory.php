<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineOrmFilter\Test\Factory;

use Atlance\HttpDoctrineOrmFilter\Test\Domain\Entity\User\UserRepository;

final class UserRepositoryFactory
{
    public static function create(): UserRepository
    {
        return new UserRepository((new EntityManagerFactory())::create(), FilterFactory::create());
    }
}
