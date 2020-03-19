<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineFilter\Dto;

use Atlance\HttpDoctrineFilter\Builder\QueryBuilder;
use Webmozart\Assert\Assert;

class QueryConfiguration extends AbstractDto
{
    /** @var array */
    public $filter = [];
    /** @var array */
    public $order = [];

    public function __construct(array $conditions)
    {
        parent::__construct($conditions);
        $this->filter = json_decode((string) json_encode($this->filter, JSON_NUMERIC_CHECK + JSON_PRESERVE_ZERO_FRACTION), true);
    }

    public function setFilter(array $conditions): self
    {
        foreach ($conditions as $exp => $values) {
            foreach ($values as $propertyAlias => $value) {
                if (!array_key_exists($exp, $this->filter)) {
                    $this->filter[$exp] = [];
                }
                Assert::oneOf($exp, QueryBuilder::SUPPORTED_EXPRESSIONS);
                $this->filter[$exp][$propertyAlias] = explode('|', $value);
            }
        }

        return $this;
    }

    public function setOrder(array $conditions): self
    {
        foreach ($conditions as $alias => $direction) {
            Assert::oneOf($direction, ['asc', 'desc']);
            $this->order[$alias] = $direction;
        }

        return $this;
    }
}
