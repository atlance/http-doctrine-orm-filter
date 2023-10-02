<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineOrmFilter\Query;

use Webmozart\Assert\Assert;

final class Field
{
    /**
     * DQL expression.
     */
    private string $exprMethod;

    /**
     * Snake case DQL expression.
     */
    private string $snakeCaseExprMethod;

    /**
     * The name of the Entity class.
     */
    private string $class;

    /**
     * Table alias in current instance ORM\QueryBuilder.
     */
    private string $tableAlias;

    /**
     * The name of the field in the Entity.
     */
    private string $fieldName;

    /**
     * The column name. Optional. Defaults to the field name.
     */
    private string $columnName;

    /**
     * The type name of the mapped field. Can be one of Doctrine's mapping types or a custom mapping type.
     */
    private string $type;

    /**
     * The database length of the column. Optional. Default value taken from the type.
     */
    private ?int $length;

    /**
     * Marks the field as the primary key of the entity. Multiple fields of an entity can have the id attribute,
     * forming a composite key.
     */
    private ?bool $id;

    /**
     * Whether the column is nullable. Defaults to FALSE.
     */
    private ?bool $nullable;

    /**
     * The SQL fragment that is used when generating the DDL for the column.
     */
    private ?string $columnDefinition;

    /**
     * The precision of a decimal column. Only valid if the column type is decimal.
     */
    private ?int $precision;

    /**
     * The scale of a decimal column. Only valid if the column type is decimal.
     */
    private ?int $scale;

    /**
     * Whether a unique constraint should be generated for the column.
     */
    private ?bool $unique;

    /**
     * Is LIKE operator?
     */
    private bool $isLike;

    private array $values = [];

    public function __construct(string $snakeCaseExprMethod, string $class, string $tableAlias)
    {
        Assert::oneOf($snakeCaseExprMethod, Builder::SUPPORTED_EXPRESSIONS);
        $this->snakeCaseExprMethod = $snakeCaseExprMethod;
        $this->isLike = \in_array($snakeCaseExprMethod, ['like', 'not_like', 'ilike'], true);
        $exprMethod = lcfirst(str_replace('_', '', ucwords($snakeCaseExprMethod, '_')));
        Assert::methodExists(Builder::class, $exprMethod, sprintf('method "%s" not allowed', $exprMethod));
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

    public function getColumnName(): string
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
        return \count($this->values);
    }

    public function generateParameter(null | string | int $i = null): string
    {
        return null === $i
            ? sprintf(':%s_%s', $this->getTableAlias(), $this->getColumnName())
            : sprintf(':%s_%s_%s', $this->getTableAlias(), $this->getColumnName(), $i);
    }

    public function getPropertyPath(bool $isOrm = true): string
    {
        return $isOrm
            ? sprintf('%s.%s', $this->getTableAlias(), $this->getFieldName())
            : sprintf('%s.%s', $this->getTableAlias(), $this->getColumnName());
    }

    public function isLike(): bool
    {
        return $this->isLike;
    }

    public function initProperties(array $properties): self
    {
        /**
         * @var string $property
         * @var mixed  $value
         */
        foreach ($properties as $property => $value) {
            if (property_exists($this, $property)) {
                $this->{$property} = $value;
            }
        }

        return $this;
    }
}
