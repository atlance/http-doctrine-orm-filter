<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineOrmFilter\Query;

use Webmozart\Assert\Assert;

class Configuration extends AbstractCommand
{
    public array $filter = [];
    public array $order = [];

    /**
     * @psalm-suppress MixedAssignment
     */
    public function __construct(array $conditions)
    {
        parent::__construct($conditions);
        $this->filter = json_decode(
            json_encode(
                $this->filter,
                \JSON_THROW_ON_ERROR | \JSON_NUMERIC_CHECK + \JSON_PRESERVE_ZERO_FRACTION
            ),
            true,
            512,
            \JSON_THROW_ON_ERROR
        );
    }

    /** @psalm-suppress MixedArrayAssignment */
    public function setFilter(array $conditions): self
    {
        /**
         * @var string $exp
         * @var array  $values
         */
        foreach ($conditions as $exp => $values) {
            /**
             * @var string $propertyAlias
             * @var string $value
             */
            foreach ($values as $propertyAlias => $value) {
                if (!\array_key_exists($exp, $this->filter)) {
                    $this->filter[$exp] = [];
                }
                Assert::oneOf($exp, Builder::SUPPORTED_EXPRESSIONS);
                $this->filter[$exp][$propertyAlias] = explode('|', $value);
            }
        }

        return $this;
    }

    public function setOrder(array $conditions): self
    {
        /**
         * @var string $alias
         * @var string $direction
         */
        foreach ($conditions as $alias => $direction) {
            Assert::oneOf($direction, ['asc', 'desc']);
            $this->order[$alias] = $direction;
        }

        return $this;
    }
}
