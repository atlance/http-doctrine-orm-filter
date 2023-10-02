<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineOrmFilter\Query;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Webmozart\Assert\Assert;

final class Builder
{
    /** @var string[] */
    public const SUPPORTED_EXPRESSIONS = [
        'eq',
        'neq',
        'gt',
        'gte',
        'in',
        'not_in',
        'is_null',
        'is_not_null',
        'ilike',
        'like',
        'not_like',
        'lt',
        'lte',
        'between',
        'order_by',
    ];

    public function __construct(private readonly QueryBuilder $qb)
    {
    }

    public function andWhere(Field $field): void
    {
        $this->{$field->getExprMethod()}($field);
    }

    private function andWhereAndX(Field $field): void
    {
        $this->andWhereComposite($field, CompositeExpression::TYPE_AND);
    }

    /**
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedArgumentTypeCoercion
     */
    private function andWhereComposite(Field $field, string $type): void
    {
        Assert::inArray($type, [CompositeExpression::TYPE_AND, CompositeExpression::TYPE_OR]);
        $composite = CompositeExpression::TYPE_AND === $type ? $this->qb->expr()->andX() : $this->qb->expr()->orX();
        $parts = [];
        /**
         * @var int   $i
         * @var mixed $value
         */
        foreach ($field->getValues() as $i => $value) {
            /** @var string|Expr $expr */
            $expr = $this->qb->expr()->{$field->getExprMethod()}(
                $field->getPropertyPath(),
                $field->generateParameter($i)
            );

            $parts[] = $expr;
            if ($field->isLike()) {
                $this->qb->setParameter($field->generateParameter($i), "%{$value}%");

                continue;
            }

            $this->qb->setParameter($field->generateParameter($i), $value);
        }

        $this->qb->andWhere($composite->addMultiple($parts));
    }

    private function andWhereOrX(Field $field): void
    {
        $this->andWhereComposite($field, CompositeExpression::TYPE_OR);
    }

    private function between(Field $field): void
    {
        Assert::eq($field->countValues(), 2, 'Invalid format for between, expected "min|max"');
        [$min, $max] = $field->getValues();
        Assert::lessThan($min, $max, 'Invalid values for between, expected min < max');

        $from = $field->generateParameter('from');
        $to = $field->generateParameter('to');
        $this->qb->andWhere(sprintf('%s BETWEEN %s AND %s', $field->getPropertyPath(), $from, $to))
            ->setParameter($from, $min)
            ->setParameter($to, $max);
    }

    private function eq(Field $field): void
    {
        $this->andWhereOrX($field);
    }

    private function gt(Field $field): void
    {
        Assert::eq($field->countValues(), 1, 'expected single value');
        $this->qb->andWhere($this->qb->expr()->gt($field->getPropertyPath(), $field->generateParameter('gt')))
            ->setParameter($field->generateParameter('gt'), $field->getValues()[0]);
    }

    private function gte(Field $field): void
    {
        Assert::eq($field->countValues(), 1, 'expected single value');
        $this->qb->andWhere($this->qb->expr()->gte($field->getPropertyPath(), $field->generateParameter('gte')))
            ->setParameter($field->generateParameter('gte'), $field->getValues()[0]);
    }

    private function ilike(Field $field): void
    {
        $parts = [];
        $composite = $this->qb->expr()->orX();
        /**
         * @var int   $i
         * @var mixed $value
         */
        foreach ($field->getValues() as $i => $value) {
            $parts[] = $this->qb->expr()->like(
                sprintf('LOWER(%s)', $field->getPropertyPath()),
                sprintf('LOWER(%s)', $field->generateParameter($i))
            );

            $this->qb->setParameter($field->generateParameter($i), mb_strtolower("%{$value}%"));
        }

        $this->qb->andWhere($composite->addMultiple($parts));
    }

    private function in(Field $field): void
    {
        Assert::greaterThanEq(
            $field->countValues(),
            2,
            'expression "in" expected multiple value. Use "eq" for single value.'
        );

        $this->qb->andWhere($this->qb->expr()->in($field->getPropertyPath(), $field->generateParameter('in')))
            ->setParameter(
                $field->generateParameter('in'),
                $field->getValues(),
                \is_string($field->getValues()[0])
                    ? Connection::PARAM_STR_ARRAY
                    : Connection::PARAM_INT_ARRAY
            );
    }

    private function isNotNull(Field $field): void
    {
        $this->qb->andWhere($this->qb->expr()->isNotNull($field->getPropertyPath()));
    }

    private function isNull(Field $field): void
    {
        $this->qb->andWhere($this->qb->expr()->isNull($field->getPropertyPath()));
    }

    private function like(Field $field): void
    {
        $this->andWhereOrX($field);
    }

    private function lt(Field $field): void
    {
        Assert::eq($field->countValues(), 1, 'expected single value');
        $this->qb->andWhere($this->qb->expr()->lt($field->getPropertyPath(), $field->generateParameter('lt')))
            ->setParameter($field->generateParameter('lt'), $field->getValues()[0]);
    }

    private function lte(Field $field): void
    {
        Assert::eq($field->countValues(), 1, 'expected single value');
        $this->qb->andWhere($this->qb->expr()->lte($field->getPropertyPath(), $field->generateParameter('lte')))
            ->setParameter($field->generateParameter('lte'), $field->getValues()[0]);
    }

    private function neq(Field $field): void
    {
        $this->andWhereAndX($field);
    }

    private function notIn(Field $field): void
    {
        Assert::greaterThanEq(
            $field->countValues(),
            2,
            'expression "not_in" expected multiple value. Use "eq" for single value.'
        );
        $this->qb->andWhere($this->qb->expr()->notIn($field->getPropertyPath(), $field->generateParameter('not_in')))
            ->setParameter(
                $field->generateParameter('not_in'),
                $field->getValues(),
                \is_string($field->getValues()[0])
                    ? Connection::PARAM_STR_ARRAY
                    : Connection::PARAM_INT_ARRAY
            );
    }

    private function notLike(Field $field): void
    {
        $this->andWhereAndX($field);
    }

    private function orderBy(Field $field): void
    {
        Assert::eq($field->countValues(), 1, 'expected single value');
        $order = $field->getValues()[0];
        Assert::true(\is_string($order));
        $order = mb_strtolower($order);
        Assert::true('asc' === $order || 'desc' === $order);
        $this->qb->addOrderBy($field->getPropertyPath(), (string) $field->getValues()[0]);
    }
}
