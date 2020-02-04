<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineFilter\Dto;

class HttpDoctrineFilterRequest extends AbstractDto
{
    /** @var array */
    public $filter = [];

    /** @var array */
    public $order = [];

    public function __construct(array $data)
    {
        parent::__construct($data);
        $this->filter = json_decode((string) json_encode($this->filter, JSON_NUMERIC_CHECK + JSON_PRESERVE_ZERO_FRACTION), true);
    }

    public function setFilter(array $filters): self
    {
        foreach ($filters as $exp => $values) {
            foreach ($values as $propertyAlias => $value) {
                if (!array_key_exists($exp, $this->filter)) {
                    $this->filter[$exp] = [];
                }

                $this->filter[$exp][$propertyAlias] = explode('|', $value);
            }
        }

        return $this;
    }

    public function setOrder(array $orders): self
    {
        foreach ($orders as $exp => $order) {
            $this->order[$exp] = $order;
        }

        return $this;
    }
}
