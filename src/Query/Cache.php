<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineOrmFilter\Query;

use Doctrine\Common\Cache\CacheProvider;

final class Cache
{
    public function __construct(private CacheProvider $cacheProvider)
    {
    }

    public function setNamespace(string $namespace): self
    {
        $this->cacheProvider->setNamespace($namespace);

        return $this;
    }

    /**
     * @return array<string>
     */
    public function generateCacheKeys(string $key, string $query, array $params = []): array
    {
        $realCacheKey = $key . 'query=' . $query . '&params=' . hash('sha256', serialize($params));

        return [sha1($realCacheKey), $realCacheKey];
    }

    /**
     * @return false|mixed
     */
    public function fetchCache(string $cacheKey): mixed
    {
        return $this->cacheProvider->fetch($cacheKey);
    }

    public function saveCache(string $cacheKey, mixed $data, int $lifeTime = 0): bool
    {
        return $this->cacheProvider->save($cacheKey, $data, $lifeTime);
    }
}
