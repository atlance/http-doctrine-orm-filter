<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineOrmFilter\Test\Factory;

use Atlance\HttpDoctrineOrmFilter\Query\Validator;
use Symfony\Component\Validator\Validation;

final class ValidatorFactory
{
    public static function create(): Validator
    {
        return new Validator(Validation::createValidatorBuilder()->enableAnnotationMapping()->getValidator());
    }
}
