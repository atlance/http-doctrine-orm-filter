<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineOrmFilter\Test\Factory;

use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ValidatorFactory
{
    public static function create(): ValidatorInterface
    {
        return Validation::createValidatorBuilder()->enableAnnotationMapping()->getValidator();
    }
}
