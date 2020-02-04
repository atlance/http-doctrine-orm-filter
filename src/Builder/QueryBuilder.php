<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineFilter\Builder;

use Atlance\HttpDoctrineFilter\Dto\Field;
use Doctrine\ORM\Query\Expr\Composite;
use Doctrine\ORM\QueryBuilder as OrmQueryBuilder;
use Webmozart\Assert\Assert;

final class QueryBuilder
{
    /** @var OrmQueryBuilder */
    private $qb;

    public function __construct(OrmQueryBuilder $qb)
    {
        $this->qb = $qb;
    }

    public function andWhere(Field $field): void
    {
        $this->{$field->getExprMethod()}($field);
    }

    private function andWhereAndX(Field $field): void
    {
        $this->andWhereComposite($this->qb->expr()->andX(), $field);
    }

    private function andWhereComposite(Composite $expr, Field $field): void
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

    private function andWhereOrX(Field $field): void
    {
        $this->andWhereComposite($this->qb->expr()->orX(), $field);
    }

    private function between(Field $field): void
    {
        Assert::eq($field->countValues(), 2, 'Invalid format for between, expected "min|max"');
        [$min, $max] = $field->getValues();
        Assert::lessThan($min, $max, 'Invalid values for between, expected min < max');

        $this->qb->andWhere($this->qb->expr()->between($field->getDqlPropertyFullPath(), ':from', ':to'))
            ->setParameter('from', $min)
            ->setParameter('to', $max);
    }

    private function eq(Field $field): void
    {
        $this->andWhereOrX($field);
    }

    private function gt(Field $field): void
    {
        Assert::eq($field->countValues(), 1, 'expected single value');
        $this->qb->andWhere($this->qb->expr()->gt($field->getDqlPropertyFullPath(), ":{$field->getDqlParameter()}"))
            ->setParameter($field->getDqlParameter(), $field->getValues());
    }

    private function gte(Field $field): void
    {
        Assert::eq($field->countValues(), 1, 'expected single value');
        $this->qb->andWhere($this->qb->expr()->gte($field->getDqlPropertyFullPath(), ":{$field->getDqlParameter()}"))
            ->setParameter($field->getDqlParameter(), $field->getValues());
    }

    private function ilike(Field $field): void
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

    private function in(Field $field): void
    {
        Assert::greaterThanEq($field->countValues(), 2, 'expression "in" expected multiple value. Use "eq" for single value.');
        $this->qb->andWhere($this->qb->expr()->in($field->getDqlPropertyFullPath(), ":{$field->getDqlParameter()}"))
            ->setParameter($field->getDqlParameter(), $field->getValues());
    }

    private function isNotNull(Field $field): void
    {
        $this->qb->andWhere($this->qb->expr()->isNotNull($field->getDqlPropertyFullPath()));
    }

    private function isNull(Field $field): void
    {
        $this->qb->andWhere($this->qb->expr()->isNull($field->getDqlPropertyFullPath()));
    }

    private function like(Field $field): void
    {
        $this->andWhereOrX($field);
    }

    private function lt(Field $field): void
    {
        Assert::eq($field->countValues(), 1, 'expected single value');
        $this->qb->andWhere($this->qb->expr()->lt($field->getDqlPropertyFullPath(), ":{$field->getDqlParameter()}"))
            ->setParameter($field->getDqlParameter(), $field->getValues());
    }

    private function lte(Field $field): void
    {
        Assert::eq($field->countValues(), 1, 'expected single value');
        $this->qb->andWhere($this->qb->expr()->lte($field->getDqlPropertyFullPath(), ":{$field->getDqlParameter()}"))
            ->setParameter($field->getDqlParameter(), $field->getValues());
    }

    private function neq(Field $field): void
    {
        $this->andWhereAndX($field);
    }

    private function notIn(Field $field): void
    {
        Assert::greaterThanEq($field->countValues(), 2, 'expression "not_in" expected multiple value. Use "eq" for single value.');
        $this->qb->andWhere($this->qb->expr()->notIn($field->getDqlPropertyFullPath(), ":{$field->getDqlParameter()}"))
            ->setParameter($field->getDqlParameter(), $field->getValues());
    }

    private function notLike(Field $field): void
    {
        $this->andWhereAndX($field);
    }

    private function orderBy(Field $field): void
    {
        Assert::eq($field->countValues(), 1, 'expected single value');
        $order = $field->getValues()[0];
        Assert::true(is_string($order));
        $order = strtolower($order);
        Assert::true($order === 'asc' || $order === 'desc');
        $this->qb->addOrderBy($field->getDqlPropertyFullPath(), (string) $field->getValues()[0]);
    }
}
