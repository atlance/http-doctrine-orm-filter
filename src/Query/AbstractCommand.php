<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineOrmFilter\Query;

/**
 * @psalm-suppress MixedArgumentTypeCoercion
 */
abstract class AbstractCommand
{
    public function __construct(array $properties = [])
    {
        $this->setup($properties);
    }

    private function setup(array $properties): void
    {
        /** @psalm-var mixed $value */
        foreach ($properties as $property => $value) {
            /** @psalm-var string $property */
            if (property_exists(static::class, $property)) {
                $method = 'set' . str_replace(
                        ' ',
                        '',
                        mb_convert_case(
                            str_replace('_', ' ', $property),
                            \MB_CASE_TITLE,
                            'UTF-8'
                        )
                    );
                if (\is_callable([$this, $method])) {
                    $this->{$method}($value); /* @phpstan-ignore-line */

                    continue;
                }
            }
        }
    }
}
