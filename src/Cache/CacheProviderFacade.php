<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineFilter\Cache;

use Doctrine\Common\Cache\CacheProvider;

final class CacheProviderFacade
{
    /** @var CacheProvider */
    private $cacheProvider;

    public function __construct(CacheProvider $cacheProvider)
    {
        $this->cacheProvider = $cacheProvider;
    }

    public function getCacheProvider(): CacheProvider
    {
        return $this->cacheProvider;
    }

    public function setCacheProvider(CacheProvider $cacheProvider): self
    {
        $this->cacheProvider = $cacheProvider;

        return $this;
    }

    public function setNamespace(string $namespace): self
    {
        $this->cacheProvider->setNamespace($namespace);

        return $this;
    }

    public function generateCacheKeys(string $key, string $dql, array $params = []): array
    {
        $realCacheKey = 'dql='.$dql.
            '&params='.hash('sha256', serialize($params)).
            '&key='.$key;

        return [sha1($realCacheKey), $realCacheKey];
    }

    public function deleteCache(string $cacheKey): bool
    {
        return $this->cacheProvider->delete($cacheKey);
    }

    public function deleteAllCache(): bool
    {
        return $this->cacheProvider->deleteAll();
    }

    /**
     * @return false|mixed
     */
    public function fetchCache(string $cacheKey)
    {
        return $this->cacheProvider->fetch($cacheKey);
    }

    /**
     * @param mixed $data
     */
    public function saveCache(string $cacheKey, $data): bool
    {
        return $this->cacheProvider->save($cacheKey, $data);
    }
}
