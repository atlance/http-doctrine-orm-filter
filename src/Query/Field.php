<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineOrmFilter\Query;

use Webmozart\Assert\Assert;

final class Field
{
    /**
     * DQL expression.
     *
     * @var string
     */
    private $exprMethod;

    /**
     * Snake case DQL expression.
     *
     * @var string
     */
    private $snakeCaseExprMethod;

    /**
     * The name of the Entity class.
     *
     * @var string
     */
    private $class;

    /**
     * Table alias in current instance ORM\QueryBuilder.
     *
     * @var string
     */
    private $tableAlias;

    /**
     * The name of the field in the Entity.
     *
     * @var string
     */
    private $fieldName;

    /**
     * The column name. Optional. Defaults to the field name.
     *
     * @var string|null
     */
    private $columnName;

    /**
     * The type name of the mapped field. Can be one of Doctrine's mapping types or a custom mapping type.
     *
     * @var string
     */
    private $type;

    /**
     * The database length of the column. Optional. Default value taken from the type.
     *
     * @var int|null
     */
    private $length;

    /**
     * Marks the field as the primary key of the entity. Multiple fields of an entity can have the id attribute,
     * forming a composite key.
     *
     * @var bool|null
     */
    private $id;

    /**
     * Whether the column is nullable. Defaults to FALSE.
     *
     * @var bool|null
     */
    private $nullable;

    /**
     * The SQL fragment that is used when generating the DDL for the column.
     *
     * @var string|null
     */
    private $columnDefinition;

    /**
     * The precision of a decimal column. Only valid if the column type is decimal.
     *
     * @var int|null
     */
    private $precision;

    /**
     * The scale of a decimal column. Only valid if the column type is decimal.
     *
     * @var int|null
     */
    private $scale;

    /**
     * Whether a unique constraint should be generated for the column.
     *
     * @var bool|null
     */
    private $unique;

    /**
     * Is LIKE operator?
     *
     * @var bool
     */
    private $isLike;

    /**
     * @var array
     */
    private $values;

    public function __construct(string $snakeCaseExprMethod, string $class, string $tableAlias)
    {
        Assert::oneOf($snakeCaseExprMethod, Builder::SUPPORTED_EXPRESSIONS);
        $this->snakeCaseExprMethod = $snakeCaseExprMethod;
        $this->isLike = in_array($snakeCaseExprMethod, ['like', 'not_like', 'ilike'], true);
        $exprMethod = lcfirst(str_replace('_', '', ucwords($snakeCaseExprMethod, '_')));
        Assert::methodExists(Builder::class, $exprMethod, "method \"{$exprMethod}\" not allowed");
        $this->exprMethod = $exprMethod;
        $this->class = $class;
        $this->tableAlias = $tableAlias;
    }

    public function getExprMethod(): string
    {
        return $this->exprMethod;
    }

    public function getSnakeCaseExprMethod(): string
    {
        return $this->snakeCaseExprMethod;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getTableAlias(): string
    {
        return $this->tableAlias;
    }

    public function getFieldName(): string
    {
        return $this->fieldName;
    }

    public function getColumnName(): ?string
    {
        return $this->columnName;
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function setValues(array $values): self
    {
        $this->values = $values;

        return $this;
    }

    public function countValues(): int
    {
        return count($this->values);
    }

    public function generateParameter(null | string | int $i = null): string
    {
        return null === $i
            ? ":{$this->getTableAlias()}_{$this->getColumnName()}"
            : ":{$this->getTableAlias()}_{$this->getColumnName()}_{$i}";
    }

    public function getPropertyPath(bool $isOrm = true): string
    {
        return true === $isOrm
            ? "{$this->getTableAlias()}.{$this->getFieldName()}"
            : "{$this->getTableAlias()}.{$this->getColumnName()}";
    }

    public function isLike(): bool
    {
        return $this->isLike;
    }

    public function initProperties(array $properties): self
    {
        /**
         * @var string $property
         * @var mixed $value
         */
        foreach ($properties as $property => $value) {
            if (property_exists($this, $property)) {
                $this->{$property} = $value;
            }
        }

        return $this;
    }
}
