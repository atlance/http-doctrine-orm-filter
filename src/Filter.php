<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineOrmFilter;

use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\From;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Validator\Exception\ValidatorException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class Filter
{
    public const CACHE_KEY_METADATA = 'metadata';
    public const CACHE_KEY_FIELD = 'field';

    private Query\Validator $validator;
    private Cache $cacher;

    public function __construct(ValidatorInterface $validator, Cache $cacher)
    {
        $this->validator = new Query\Validator($validator);
        $this->cacher = $cacher;
    }

    public function apply(QueryBuilder $qb, Query\Configuration $configuration): void
    {
        $this
            ->select($qb, $configuration->filter)
            ->order($qb, $configuration->order);
    }

    private function select(QueryBuilder $qb, array $conditions): self
    {
        /**
         * @var string $expr
         * @var array  $aliases
         */
        foreach ($conditions as $expr => $aliases) {
            /**
             * @var string $alias
             * @var array  $values
             */
            foreach ($aliases as $alias => $values) {
                /** @var string $cacheKey */
                [$cacheKey,] = Query\CacheKeyGenerator::generate(
                    self::CACHE_KEY_FIELD,
                    $qb->getDQL(),
                    ['query' => "[{$expr}][{$alias}]"]
                );
                /** @var Query\Field|null $field */
                $field = $this->cacher->fetch($cacheKey);
                if (!$field instanceof Query\Field) {
                    $field = $this->createField($qb, $alias, $expr);
                }

                $this->createQuery($qb, $field, $values, $cacheKey);
            }
        }

        if (!$this->validator->isValid()) {
            throw new ValidatorException((string) json_encode($this->validator->getAllViolations(), \JSON_UNESCAPED_UNICODE));
        }

        return $this;
    }

    private function order(QueryBuilder $qb, array $conditions): self
    {
        /**
         * @var string $alias
         * @var string $value
         */
        foreach ($conditions as $alias => $value) {
            $snakeCaseExprMethod = 'order_by';
            /** @var string $cacheKey */
            [$cacheKey,] = Query\CacheKeyGenerator::generate(
                self::CACHE_KEY_FIELD,
                $qb->getDQL(),
                ['query' => "[{$snakeCaseExprMethod}][{$alias}]"]
            );
            /** @var Query\Field|null $field */
            $field = $this->cacher->fetch($cacheKey);
            if (false === $field instanceof Query\Field) {
                $field = $this->createField($qb, $alias, $snakeCaseExprMethod);
            }

            $this->createQuery($qb, $field, [$value], $cacheKey);
        }

        return $this;
    }

    private function createQuery(QueryBuilder $qb, Query\Field $field, array $values, string $cacheKey): void
    {
        if ($this->isValid($field, $values)) {
            (new Query\Builder($qb))->andWhere($field->setValues($values));
            $this->cacher->save($cacheKey, $field);
        }
    }

    private function createField(QueryBuilder $qb, string $tableAliasAndColumnName, string $expr): Query\Field
    {
        foreach ($this->getAliasesAndMetadata($qb) as $alias => $metadata) {
            if (0 === strncasecmp($tableAliasAndColumnName, $alias . '_', mb_strlen($alias . '_'))) {
                $columnName = mb_substr($tableAliasAndColumnName, mb_strlen($alias . '_'));

                if (\array_key_exists($columnName, $metadata->fieldNames)) {
                    return (new Query\Field($expr, $metadata->getName(), $alias))
                        ->initProperties($metadata->getFieldMapping($metadata->getFieldForColumn($columnName)));
                }
            }
        }

        throw new \InvalidArgumentException($tableAliasAndColumnName . ' not allowed');
    }

    /**
     * @psalm-suppress MixedReturnTypeCoercion
     * @psalm-suppress MixedAssignment
     *
     * @return array<string, ClassMetadata>
     */
    private function getAliasesAndMetadata(QueryBuilder $qb): array
    {
        [$cacheKey,] = Query\CacheKeyGenerator::generate(self::CACHE_KEY_METADATA, $qb->getDQL());
        if (!\is_array($aliasesAndMetadata = $this->cacher->fetch($cacheKey))) {
            $aliasesAndMetadata = [];

            foreach ($qb->getAllAliases() as $alias) {
                $metadata = $this->getMetadataByAlias($qb, $alias);
                if ($metadata instanceof ClassMetadata) {
                    $aliasesAndMetadata[$alias] = $metadata;
                }
            }
            $this->cacher->save($cacheKey, $aliasesAndMetadata);
        }

        return $aliasesAndMetadata;
    }

    private function getMetadataByAlias(QueryBuilder $qb, string $alias): ?ClassMetadata
    {
        $metadata = null;
        foreach ($this->getParts($qb) as $part) {
            if ($part->getAlias() !== $alias) {
                continue;
            }

            $metadata = $this->getMetadataByDQLPart($qb, $part);
        }

        return $metadata;
    }

    /**
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedOperand
     * @psalm-suppress MixedInferredReturnType
     * @psalm-suppress MixedArrayAssignment
     * @psalm-suppress MixedReturnStatement
     *
     * @return Expr\From[]|Expr\Join[]
     */
    private function getParts(QueryBuilder $qb): array
    {
        $parts = [];
        /** @var Expr\From[]|Expr\Join[] $tmp */
        $tmp = $qb->getDQLPart('join') + $qb->getDQLPart('from');
        array_walk_recursive($tmp, static function ($part) use (&$parts): void {$parts[] = $part; });
        unset($tmp);

        return $parts;
    }

    /**
     * For join without class name.
     * Example: ->leftJoin('users.cards', 'cards', Join::WITH).
     */
    private function getMetadataByDQLPart(QueryBuilder $qb, Join | From $partData): ClassMetadata
    {
        if (!class_exists($class = $partData instanceof From ? $partData->getFrom() : $partData->getJoin())) {
            $joinAlias = explode('.', $class)[1];

            foreach ($qb->getRootEntities() as $rootEntity) {
                $class = $qb->getEntityManager()->getClassMetadata($rootEntity)->getAssociationTargetClass($joinAlias);
            }
        }

        return $qb->getEntityManager()->getClassMetadata($class);
    }

    private function isValid(Query\Field $field, array $values): bool
    {
        $exp = $field->getSnakeCaseExprMethod();

        if ($this->skipValidate($exp)) {
            return true;
        }

        $this->validator->validatePropertyValue(
            "[{$field->getExprMethod()}][{$field->generateParameter()}]",
            $field->getClass(),
            $field->getFieldName(),
            $values
        );

        return $this->validator->isValid();
    }

    private function skipValidate(string $exp): bool
    {
        return \in_array($exp, ['is_null', 'is_not_null', 'like', 'ilike', 'not_like', 'between', 'order_by'], true);
    }
}
