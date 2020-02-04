<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineOrmFilter\Test\Utils\Cache;

use Psr\Cache\CacheItemInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Adapter\AbstractAdapter;

class SimpleCacheBridge implements CacheInterface
{
    protected AbstractAdapter $cacheItemPool;

    public function __construct(AbstractAdapter $cacheItemPool)
    {
        $this->cacheItemPool = $cacheItemPool;
    }

    /** {@inheritdoc} */
    public function get(string $key, mixed $default = null): mixed
    {
        try {
            $item = $this->cacheItemPool->getItem($key);

            if (!$item->isHit()) {
                return $default;
            }

            return $item->get();
        } catch (\Throwable $e) {
            throw $this->wrapException($e);
        }
    }

    /** {@inheritdoc} */
    public function set(string $key, mixed $value, \DateInterval | int | null $ttl = null): bool
    {
        try {
            $item = $this->cacheItemPool->getItem($key);
            $item->expiresAfter($ttl);
            $item->set($value);

            return $this->cacheItemPool->save($item);
        } catch (\Throwable $e) {
            throw $this->wrapException($e);
        }
    }

    /** {@inheritdoc} */
    public function delete(string $key): bool
    {
        try {
            return $this->cacheItemPool->deleteItem($key);
        } catch (\Throwable $e) {
            throw $this->wrapException($e);
        }
    }

    /** {@inheritdoc} */
    public function clear(): bool
    {
        return $this->cacheItemPool->clear();
    }

    /** {@inheritdoc} */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        if (!\is_array($keys)) {
            $keys = iterator_to_array($keys, false);
        }

        try {
            return $this->generateValues($default, $this->cacheItemPool->getItems($keys));
        } catch (\Throwable $e) {
            throw $this->wrapException($e);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @psalm-suppress MixedAssignment
     */
    public function setMultiple(iterable $values, \DateInterval | int | null $ttl = null): bool
    {
        /** @var string[] */
        $keys = [];
        $arrayValues = [];
        /**
         * @var string|int $key
         * @var mixed      $value
         */
        foreach ($values as $key => $value) {
            if (\is_int($key)) {
                $key = (string) $key;
            }

            $keys[] = $key;
            $arrayValues[$key] = $value;
        }

        try {
            $items = $this->cacheItemPool->getItems($keys);
            $itemSuccess = true;

            /** @var array<string, CacheItemInterface> $items */
            foreach ($items as $key => $item) {
                $item
                    ->set($arrayValues[$key])
                    ->expiresAfter($ttl);

                $itemSuccess = $itemSuccess && $this->cacheItemPool->saveDeferred($item);
            }

            return $itemSuccess && $this->cacheItemPool->commit();
        } catch (\Throwable $e) {
            throw $this->wrapException($e);
        }
    }

    /** {@inheritdoc} */
    public function deleteMultiple(iterable $keys): bool
    {
        if ($keys instanceof \Traversable) {
            $keys = iterator_to_array($keys, false);
        }

        try {
            return $this->cacheItemPool->deleteItems($keys);
        } catch (\Throwable $e) {
            throw $this->wrapException($e);
        }
    }

    /** {@inheritdoc} */
    public function has(string $key): bool
    {
        try {
            return $this->cacheItemPool->hasItem($key);
        } catch (\Throwable $e) {
            throw $this->wrapException($e);
        }
    }

    /**
     * @return iterable<string,mixed>
     */
    private function generateValues(mixed $default, iterable $items): iterable
    {
        /** @var array<string, CacheItemInterface> $items */
        foreach ($items as $key => $item) {
            if (!$item->isHit()) {
                yield $key => $default;
            } else {
                yield $key => $item->get();
            }
        }
    }

    private function wrapException(\Throwable $e): Exceptions\InvalidArgumentException
    {
        return new Exceptions\InvalidArgumentException($e->getMessage(), (int) $e->getCode(), $e);
    }
}
