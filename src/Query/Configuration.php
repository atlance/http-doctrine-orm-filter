<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineOrmFilter\Query;

use Atlance\HttpDoctrineOrmFilter\Utils\JsonNormalizer;
use Webmozart\Assert\Assert;

class Configuration extends AbstractCommand
{
    public int $page;

    public int $limit;

    public array $filter = [];

    public array $order = [];

    /** @psalm-suppress MixedArrayAssignment */
    public function setFilter(array $conditions): self
    {
        /**
         * @var string $exp
         * @var array  $values
         */
        foreach ($conditions as $exp => $values) {
            Assert::oneOf($exp, Builder::SUPPORTED_EXPRESSIONS);
            /**
             * @var string $propertyAlias
             * @var mixed  $value
             */
            foreach ($values as $propertyAlias => $value) {
                if (!\array_key_exists($exp, $this->filter)) {
                    $this->filter[$exp] = [];
                }

                if (\is_string($value) && preg_match('#\|#', $value)) {
                    $value = JsonNormalizer::normalize(explode('|', $value));
                }

                $this->filter[$exp][$propertyAlias] = \is_array($value) ? $value : [$value];
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
