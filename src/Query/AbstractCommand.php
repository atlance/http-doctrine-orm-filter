<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineOrmFilter\Query;

use Atlance\HttpDoctrineOrmFilter\Utils\SetterMethodName;

/**
 * @psalm-suppress MixedArgumentTypeCoercion
 */
abstract class AbstractCommand
{
    final public function __construct()
    {
    }

    public static function fromArray(array $properties = []): static
    {
        $object = new static();
        /** @psalm-var mixed $value */
        foreach ($properties as $property => $value) {
            /** @psalm-var string $property */
            if (property_exists(static::class, $property)) {
                $method = SetterMethodName::fromSnakeCasePropertyName($property)->getName();
                if (\is_callable([$object, $method])) {
                    $object->{$method}($value);

                    continue;
                }

                $object->{$property} = $value;
            }
        }

        return $object;
    }
}
