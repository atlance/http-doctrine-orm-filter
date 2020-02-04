<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineFilter;

use Atlance\HttpDoctrineFilter\Builder\QueryBuilder;
use Atlance\HttpDoctrineFilter\Cache\CacheProviderFacade;
use Atlance\HttpDoctrineFilter\Dto\Field;
use Atlance\HttpDoctrineFilter\Validator\ValidatorFacade;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\Mapping\ClassMetadata;
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

    /** @var OrmQueryBuilder */
    private $qb;

    /** @var ValidatorFacade */
    private $validator;

    /** @var CacheProviderFacade */
    private $cacher;

    public function __construct(OrmQueryBuilder $qb, ValidatorInterface $validator, CacheProvider $cacheProvider = null)
    {
        $this->qb = $qb;
        $this->validator = new ValidatorFacade($validator);
        $this->cacher = (new CacheProviderFacade($cacheProvider ?? new ArrayCache()))
            ->setNamespace(self::CACHE_NAMESPACE);
    }

    public function setOrmQueryBuilder(OrmQueryBuilder $qb): self
    {
        $this->qb = $qb;

        return $this;
    }

    public function getOrmQueryBuilder(): OrmQueryBuilder
    {
        return $this->qb;
    }

    public function setCacheProvider(CacheProvider $cacheProvider): self
    {
        $this->cacher = (new CacheProviderFacade($cacheProvider))->setNamespace(self::CACHE_NAMESPACE);

        return $this;
    }

    public function setValidator(ValidatorInterface $validator): self
    {
        $this->validator = new ValidatorFacade($validator);

        return $this;
    }

    public function setValidationGroups(array $groups): self
    {
        $this->validator->setGroups($groups);

        return $this;
    }

    public function selectBy(array $httpFilterQuery): self
    {
        foreach ($httpFilterQuery as $exprMethod => $tablesAliasesAndColumnNames) {
            foreach ($tablesAliasesAndColumnNames as $tableAliasAndColumnName => $values) {
                [$cacheKey,] = $this->cacher->generateCacheKeys(
                    self::CACHE_FIELD,
                    $this->qb->getDQL(),
                    ['query' => "[{$exprMethod}][{$tableAliasAndColumnName}]"]
                );

                if (!($field = $this->cacher->fetchCache($cacheKey)) instanceof Field) {
                    $field = $this->createField($tableAliasAndColumnName, $exprMethod);
                }

                $this->createQuery($field, $values, $cacheKey);
            }
        }

        if (!$this->validator->isValid()) {
            throw new ValidatorException((string) json_encode($this->validator->getAllViolations(), JSON_UNESCAPED_UNICODE));
        }

        return $this;
    }

    public function orderBy(array $tablesAliasesAndColumnNames): self
    {
        foreach ($tablesAliasesAndColumnNames as $tableAliasAndColumnName => $value) {
            if (!in_array($value, ['asc', 'desc'], true)) {
                throw new \InvalidArgumentException('order expected "asc" or "desc"');
            }
            $snakeCaseExprMethod = 'order_by';
            [$cacheKey,] = $this->cacher->generateCacheKeys(
                self::CACHE_FIELD,
                $this->qb->getDQL(),
                ['query' => "[{$snakeCaseExprMethod}][{$tableAliasAndColumnName}]"]
            );
            if (!($field = $this->cacher->fetchCache($cacheKey)) instanceof Field) {
                $field = $this->createField($tableAliasAndColumnName, $snakeCaseExprMethod);
            }

            $this->createQuery($field, [$value], $cacheKey);
        }

        return $this;
    }

    private function createQuery(Field $field, array $values, string $cacheKey): void
    {
        if ($this->isValid($field, $values)) {
            (new QueryBuilder($this->qb))->andWhere($field->setValues($values));
            $this->cacher->saveCache($cacheKey, $field);
        }
    }

    private function createField(string $tableAliasAndColumnName, string $snakeCaseExprMethod): Field
    {
        foreach ($this->getAliasesAndMetadata() as $alias => $metadata) {
            if (strncasecmp($tableAliasAndColumnName, $alias.'_', mb_strlen($alias.'_')) === 0) {
                $columnName = substr($tableAliasAndColumnName, mb_strlen($alias.'_'));

                if (array_key_exists($columnName, $metadata->fieldNames)) {
                    return (new Field($snakeCaseExprMethod, $metadata->getName(), $alias))
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
        [$cacheKey,] = $this->cacher->generateCacheKeys(self::CACHE_METADATA, $this->qb->getDQL());
        if (!is_array($aliasesAndMetadata = $this->cacher->fetchCache($cacheKey))) {
            $aliasesAndMetadata = [];
            foreach ($this->qb->getAllAliases() as $alias) {
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
        $tmp = $this->qb->getDQLPart('join') + $this->qb->getDQLPart('from');
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
            foreach ($this->qb->getRootEntities() as $rootEntity) {
                $class = $this->qb->getEntityManager()->getClassMetadata($rootEntity)->getAssociationTargetClass($joinAlias);
            }
        }

        return $this->qb->getEntityManager()->getClassMetadata($class);
    }

    private function isValid(Field $field, array $values): bool
    {
        $exp = $field->getSnakeCaseExprMethod();

        if ($this->skipValidate($exp)) {
            return true;
        }

        $this->validator->validatePropertyValue("[{$field->getExprMethod()}][{$field->getDqlParameter()}]", $field->getClass(), $field->getFieldName(), $values);

        return $this->validator->isValid();
    }

    private function skipValidate(string $exprMethod): bool
    {
        return in_array($exprMethod, ['is_null', 'is_not_null', 'like', 'ilike', 'not_like', 'between', 'order_by'], true);
    }
}
