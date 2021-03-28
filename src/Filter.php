<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineOrmFilter;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\From;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Validator\Exception\ValidatorException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class Filter
{
    public const CACHE_NAMESPACE = 'http_orm_filter_field';
    public const CACHE_METADATA = 'metadata';
    public const CACHE_FIELD = 'field';

    private EntityManagerInterface $em;
    private Query\Validator $validator;
    private Query\Cache $cacher;
    private QueryBuilder $currentQueryBuilder;

    public function __construct(EntityManagerInterface $em, ValidatorInterface $validator, CacheProvider $cacheProvider = null)
    {
        $this->em = $em;
        $this->validator = new Query\Validator($validator);
        $this->cacher = (new Query\Cache($cacheProvider ?? new ArrayCache()))
            ->setNamespace(self::CACHE_NAMESPACE);
        $this->currentQueryBuilder = $this->createQueryBuilder();
    }

    public function createQueryBuilder(): QueryBuilder
    {
        return new QueryBuilder($this->em);
    }

    public function setValidationGroups(array $groups): self
    {
        $this->validator->setGroups($groups);

        return $this;
    }

    public function apply(QueryBuilder $qb, Query\Configuration $configuration): QueryBuilder
    {
        return $this->setCurrentQueryBuilder($qb)
            ->select($configuration->filter)
            ->order($configuration->order)
            ->getCurrentQueryBuilder();
    }

    private function select(array $conditions): self
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
                [$cacheKey,] = $this->cacher->generateCacheKeys(
                    self::CACHE_FIELD,
                    $this->currentQueryBuilder->getDQL(),
                    ['query' => "[{$expr}][{$alias}]"]
                );
                /** @var null| Query\Field $field */
                $field = $this->cacher->fetchCache($cacheKey);
                if (false === $field instanceof Query\Field) {
                    /** @var Query\Field $field */
                    $field = $this->createField($alias, $expr);
                }

                $this->createQuery($field, $values, $cacheKey);
            }
        }

        if (!$this->validator->isValid()) {
            throw new ValidatorException((string) json_encode($this->validator->getAllViolations(), \JSON_UNESCAPED_UNICODE));
        }

        return $this;
    }

    private function order(array $conditions): self
    {
        /**
         * @var string $alias
         * @var string $value
         */
        foreach ($conditions as $alias => $value) {
            $snakeCaseExprMethod = 'order_by';
            /** @var string $cacheKey */
            [$cacheKey,] = $this->cacher->generateCacheKeys(
                self::CACHE_FIELD,
                $this->currentQueryBuilder->getDQL(),
                ['query' => "[{$snakeCaseExprMethod}][{$alias}]"]
            );
            /** @var null| Query\Field $field */
            $field = $this->cacher->fetchCache($cacheKey);
            if (false === $field instanceof Query\Field) {
                /** @var Query\Field $field */
                $field = $this->createField($alias, $snakeCaseExprMethod);
            }

            $this->createQuery($field, [$value], $cacheKey);
        }

        return $this;
    }

    private function createQuery(Query\Field $field, array $values, string $cacheKey): void
    {
        if ($this->isValid($field, $values)) {
            (new Query\Builder($this->currentQueryBuilder))->andWhere($field->setValues($values));
            $this->cacher->saveCache($cacheKey, $field);
        }
    }

    private function createField(string $tableAliasAndColumnName, string $expr): Query\Field
    {
        foreach ($this->getAliasesAndMetadata() as $alias => $metadata) {
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
    private function getAliasesAndMetadata(): array
    {
        [$cacheKey,] = $this->cacher->generateCacheKeys(self::CACHE_METADATA, $this->currentQueryBuilder->getDQL());
        if (!\is_array($aliasesAndMetadata = $this->cacher->fetchCache($cacheKey))) {
            $aliasesAndMetadata = [];
            /** @var string $alias */
            foreach ($this->currentQueryBuilder->getAllAliases() as $alias) {
                $metadata = $this->getMetadataByAlias($alias);
                if ($metadata instanceof ClassMetadata) {
                    $aliasesAndMetadata[$alias] = $metadata;
                }
            }
            $this->cacher->saveCache($cacheKey, $aliasesAndMetadata);
        }

        return $aliasesAndMetadata;
    }

    private function getMetadataByAlias(string $alias): ?ClassMetadata
    {
        $metadata = null;
        foreach ($this->getParts() as $part) {
            /** @var string $alias */
            if ($part->getAlias() !== $alias) {
                continue;
            }

            $metadata = $this->getMetadataByDQLPart($part);
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
    private function getParts(): array
    {
        $parts = [];
        /** @var Expr\From[]|Expr\Join[] $tmp */
        $tmp = $this->currentQueryBuilder->getDQLPart('join') + $this->currentQueryBuilder->getDQLPart('from');
        array_walk_recursive($tmp, static function ($part) use (&$parts): void {$parts[] = $part; });
        unset($tmp);

        return $parts;
    }

    /**
     * For join without class name.
     * Example: ->leftJoin('users.cards', 'cards', Join::WITH).
     *
     * @param From|Join $partData
     */
    private function getMetadataByDQLPart($partData): ClassMetadata
    {
        if (!class_exists($class = $partData instanceof From ? $partData->getFrom() : $partData->getJoin())) {
            $joinAlias = explode('.', $class)[1];
            /** @var string $rootEntity */
            foreach ($this->currentQueryBuilder->getRootEntities() as $rootEntity) {
                $class = $this->currentQueryBuilder->getEntityManager()->getClassMetadata($rootEntity)->getAssociationTargetClass($joinAlias);
            }
        }

        return $this->currentQueryBuilder->getEntityManager()->getClassMetadata($class);
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

    private function getCurrentQueryBuilder(): QueryBuilder
    {
        return $this->currentQueryBuilder;
    }

    private function setCurrentQueryBuilder(QueryBuilder $qb): self
    {
        $this->currentQueryBuilder = $qb;

        return $this;
    }
}
