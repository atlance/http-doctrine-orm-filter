<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineFilter\Builder;

use Atlance\HttpDoctrineFilter\Dto\QueryField;
use Doctrine\ORM\Query\Expr\Composite;
use Doctrine\ORM\QueryBuilder as OrmQueryBuilder;
use Webmozart\Assert\Assert;

final class QueryBuilder
{
    public const SUPPORTED_EXPRESSIONS = [
        'eq',
        'neq',
        'gt',
        'gte',
        'ilike',
        'in',
        'not_in',
        'is_null',
        'is_not_null',
        'like',
        'not_like',
        'lt',
        'lte',
        'between',
        'order_by',
    ];

    /** @var OrmQueryBuilder */
    private $qb;

    public function __construct(OrmQueryBuilder $qb)
    {
        $this->qb = $qb;
    }

    public function andWhere(QueryField $field): void
    {
        $this->{$field->getExprMethod()}($field);
    }

    private function andWhereAndX(QueryField $field): void
    {
        $this->andWhereComposite($this->qb->expr()->andX(), $field);
    }

    private function andWhereComposite(Composite $expr, QueryField $field): void
    {
        foreach ($field->getValues() as $i => $value) {
            $expr->add($this->qb->expr()->{$field->getExprMethod()}($field->getDqlPropertyFullPath(), ":{$field->getDqlParameter()}_{$i}"));
            if ($field->isLike() === true) {
                $this->qb->setParameter("{$field->getDqlParameter()}_{$i}", "%{$value}%");

                continue;
            }

            $this->qb->setParameter("{$field->getDqlParameter()}_{$i}", $value);
        }
        $this->qb->andWhere($expr);
    }

    private function andWhereOrX(QueryField $field): void
    {
        $this->andWhereComposite($this->qb->expr()->orX(), $field);
    }

    private function between(QueryField $field): void
    {
        Assert::eq($field->countValues(), 2, 'Invalid format for between, expected "min|max"');
        [$min, $max] = $field->getValues();
        Assert::lessThan($min, $max, 'Invalid values for between, expected min < max');

        $this->qb->andWhere($this->qb->expr()->between($field->getDqlPropertyFullPath(), ':from', ':to'))
            ->setParameter('from', $min)
            ->setParameter('to', $max);
    }

    private function eq(QueryField $field): void
    {
        $this->andWhereOrX($field);
    }

    private function gt(QueryField $field): void
    {
        Assert::eq($field->countValues(), 1, 'expected single value');
        $this->qb->andWhere($this->qb->expr()->gt($field->getDqlPropertyFullPath(), ":{$field->getDqlParameter()}"))
            ->setParameter($field->getDqlParameter(), $field->getValues());
    }

    private function gte(QueryField $field): void
    {
        Assert::eq($field->countValues(), 1, 'expected single value');
        $this->qb->andWhere($this->qb->expr()->gte($field->getDqlPropertyFullPath(), ":{$field->getDqlParameter()}"))
            ->setParameter($field->getDqlParameter(), $field->getValues());
    }

    private function ilike(QueryField $field): void
    {
        $orX = $this->qb->expr()->orX();
        foreach ($field->getValues() as $i => $value) {
            $orX->add(
                $this->qb->expr()->like(
                    (string) $this->qb->expr()->lower($field->getDqlPropertyFullPath()),
                    (string) $this->qb->expr()->lower(":{$field->getDqlParameter()}_{$i}")
                )
            );
            $this->qb->setParameter("{$field->getDqlParameter()}_{$i}", strtolower("%{$value}%"));
        }
        $this->qb->andWhere($orX);
    }

    private function in(QueryField $field): void
    {
        Assert::greaterThanEq($field->countValues(), 2, 'expression "in" expected multiple value. Use "eq" for single value.');
        $this->qb->andWhere($this->qb->expr()->in($field->getDqlPropertyFullPath(), ":{$field->getDqlParameter()}"))
            ->setParameter($field->getDqlParameter(), $field->getValues());
    }

    private function isNotNull(QueryField $field): void
    {
        $this->qb->andWhere($this->qb->expr()->isNotNull($field->getDqlPropertyFullPath()));
    }

    private function isNull(QueryField $field): void
    {
        $this->qb->andWhere($this->qb->expr()->isNull($field->getDqlPropertyFullPath()));
    }

    private function like(QueryField $field): void
    {
        $this->andWhereOrX($field);
    }

    private function lt(QueryField $field): void
    {
        Assert::eq($field->countValues(), 1, 'expected single value');
        $this->qb->andWhere($this->qb->expr()->lt($field->getDqlPropertyFullPath(), ":{$field->getDqlParameter()}"))
            ->setParameter($field->getDqlParameter(), $field->getValues());
    }

    private function lte(QueryField $field): void
    {
        Assert::eq($field->countValues(), 1, 'expected single value');
        $this->qb->andWhere($this->qb->expr()->lte($field->getDqlPropertyFullPath(), ":{$field->getDqlParameter()}"))
            ->setParameter($field->getDqlParameter(), $field->getValues());
    }

    private function neq(QueryField $field): void
    {
        $this->andWhereAndX($field);
    }

    private function notIn(QueryField $field): void
    {
        Assert::greaterThanEq($field->countValues(), 2, 'expression "not_in" expected multiple value. Use "eq" for single value.');
        $this->qb->andWhere($this->qb->expr()->notIn($field->getDqlPropertyFullPath(), ":{$field->getDqlParameter()}"))
            ->setParameter($field->getDqlParameter(), $field->getValues());
    }

    private function notLike(QueryField $field): void
    {
        $this->andWhereAndX($field);
    }

    private function orderBy(QueryField $field): void
    {
        Assert::eq($field->countValues(), 1, 'expected single value');
        $order = $field->getValues()[0];
        Assert::true(is_string($order));
        $order = strtolower($order);
        Assert::true($order === 'asc' || $order === 'desc');
        $this->qb->addOrderBy($field->getDqlPropertyFullPath(), (string) $field->getValues()[0]);
    }
}
