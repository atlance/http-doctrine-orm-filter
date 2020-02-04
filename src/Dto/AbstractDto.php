<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineFilter\Dto;

abstract class AbstractDto
{
    public function __construct(array $data)
    {
        $this->setup(array_intersect_key($data, $this->toArray()));
    }

    private function setup(array $properties): self
    {
        foreach ($properties as $property => $value) {
            $method = 'set'.ucfirst($property);
            if (is_callable([$this, $method])) {
                $this->{$method}($value);

                continue;
            }

            $this->{$property} = $value;
        }

        return $this;
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
