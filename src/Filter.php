<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineFilter;

use Atlance\HttpDoctrineFilter\Builder\QueryBuilder;
use Atlance\HttpDoctrineFilter\Cache\CacheProviderFacade;
use Atlance\HttpDoctrineFilter\Dto\QueryConfiguration;
use Atlance\HttpDoctrineFilter\Dto\QueryField;
use Atlance\HttpDoctrineFilter\Validator\ValidatorFacade;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\From;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder as OrmQueryBuilder;
use InvalidArgumentException;
use Symfony\Component\Validator\Exception\ValidatorException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class Filter
{
    public const CACHE_NAMESPACE = 'http_doctrine_filter';
    public const CACHE_METADATA = 'metadata';
    public const CACHE_FIELD = 'field';

    /** @var EntityManagerInterface */
    private $em;

    /** @var ValidatorFacade */
    private $validator;

    /** @var CacheProviderFacade */
    private $cacher;

    /** @var OrmQueryBuilder */
    private $currentQueryBuilder;

    public function __construct(EntityManagerInterface $em, ValidatorInterface $validator, CacheProvider $cacheProvider = null)
    {
        $this->em = $em;
        $this->validator = new ValidatorFacade($validator);
        $this->cacher = (new CacheProviderFacade($cacheProvider ?? new ArrayCache()))
            ->setNamespace(self::CACHE_NAMESPACE);
        $this->currentQueryBuilder = $this->createQueryBuilder();
    }

    public function createQueryBuilder(): OrmQueryBuilder
    {
        return new OrmQueryBuilder($this->em);
    }

    public function setValidationGroups(array $groups): self
    {
        $this->validator->setGroups($groups);

        return $this;
    }

    public function apply(OrmQueryBuilder $qb, QueryConfiguration $configuration): Query
    {
        return $this->setCurrentQueryBuilder($qb)
            ->select($configuration->filter)
            ->order($configuration->order)
            ->getCurrentQueryBuilder()
            ->getQuery();
    }

    private function select(array $conditions): self
    {
        foreach ($conditions as $expr => $aliases) {
            foreach ($aliases as $alias => $values) {
                [$cacheKey,] = $this->cacher->generateCacheKeys(
                    self::CACHE_FIELD,
                    $this->currentQueryBuilder->getDQL(),
                    ['query' => "[{$expr}][{$alias}]"]
                );

                if (!($field = $this->cacher->fetchCache($cacheKey)) instanceof QueryField) {
                    $field = $this->createField($alias, $expr);
                }

                $this->createQuery($field, $values, $cacheKey);
            }
        }

        if (!$this->validator->isValid()) {
            throw new ValidatorException((string) json_encode($this->validator->getAllViolations(), JSON_UNESCAPED_UNICODE));
        }

        return $this;
    }

    private function order(array $conditions): self
    {
        foreach ($conditions as $alias => $value) {
            $snakeCaseExprMethod = 'order_by';
            [$cacheKey,] = $this->cacher->generateCacheKeys(
                self::CACHE_FIELD,
                $this->currentQueryBuilder->getDQL(),
                ['query' => "[{$snakeCaseExprMethod}][{$alias}]"]
            );
            if (!($field = $this->cacher->fetchCache($cacheKey)) instanceof QueryField) {
                $field = $this->createField($alias, $snakeCaseExprMethod);
            }

            $this->createQuery($field, [$value], $cacheKey);
        }

        return $this;
    }

    private function createQuery(QueryField $field, array $values, string $cacheKey): void
    {
        if ($this->isValid($field, $values)) {
            (new QueryBuilder($this->currentQueryBuilder))->andWhere($field->setValues($values));
            $this->cacher->saveCache($cacheKey, $field);
        }
    }

    private function createField(string $tableAliasAndColumnName, string $expr): QueryField
    {
        foreach ($this->getAliasesAndMetadata() as $alias => $metadata) {
            if (strncasecmp($tableAliasAndColumnName, $alias.'_', mb_strlen($alias.'_')) === 0) {
                $columnName = substr($tableAliasAndColumnName, mb_strlen($alias.'_'));

                if (array_key_exists($columnName, $metadata->fieldNames)) {
                    return (new QueryField($expr, $metadata->getName(), $alias))
                        ->initProperties($metadata->getFieldMapping($metadata->getFieldForColumn($columnName)));
                }
            }
        }

        throw new InvalidArgumentException($tableAliasAndColumnName.' not allowed');
    }

    /**
     * @return ClassMetadata[]
     */
    private function getAliasesAndMetadata(): array
    {
        [$cacheKey,] = $this->cacher->generateCacheKeys(self::CACHE_METADATA, $this->currentQueryBuilder->getDQL());
        if (!is_array($aliasesAndMetadata = $this->cacher->fetchCache($cacheKey))) {
            $aliasesAndMetadata = [];
            foreach ($this->currentQueryBuilder->getAllAliases() as $alias) {
                $metadata = $this->getMetadataByAlias($alias);
                $aliasesAndMetadata[$alias] = $metadata;
            }
            $this->cacher->saveCache($cacheKey, $aliasesAndMetadata);
        }

        return $aliasesAndMetadata;
    }

    private function getMetadataByAlias(string $alias): ?ClassMetadata
    {
        $metadata = null;
        foreach ($this->getParts() as $part) {
            if ($part->getAlias() !== $alias) {
                continue;
            }

            $metadata = $this->getMetadataByDQLPart($part);
        }

        return $metadata;
    }

    private function getParts(): array
    {
        $parts = [];
        $tmp = $this->currentQueryBuilder->getDQLPart('join') + $this->currentQueryBuilder->getDQLPart('from');
        array_walk_recursive($tmp, function ($part) use (&$parts): void {array_push($parts, $part); });
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
            foreach ($this->currentQueryBuilder->getRootEntities() as $rootEntity) {
                $class = $this->currentQueryBuilder->getEntityManager()->getClassMetadata($rootEntity)->getAssociationTargetClass($joinAlias);
            }
        }

        return $this->currentQueryBuilder->getEntityManager()->getClassMetadata($class);
    }

    private function isValid(QueryField $field, array $values): bool
    {
        $exp = $field->getSnakeCaseExprMethod();

        if ($this->skipValidate($exp)) {
            return true;
        }

        $this->validator->validatePropertyValue("[{$field->getExprMethod()}][{$field->getDqlParameter()}]", $field->getClass(), $field->getFieldName(), $values);

        return $this->validator->isValid();
    }

    private function skipValidate(string $exp): bool
    {
        return in_array($exp, ['is_null', 'is_not_null', 'like', 'ilike', 'not_like', 'between', 'order_by'], true);
    }

    private function getCurrentQueryBuilder(): OrmQueryBuilder
    {
        return $this->currentQueryBuilder;
    }

    private function setCurrentQueryBuilder(OrmQueryBuilder $qb): self
    {
        $this->currentQueryBuilder = $qb;

        return $this;
    }
}
