<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineOrmFilter\Test\Factory;

use Symfony\Component\Cache\Adapter;

final class CacheFactory
{
    public static function create(): ?Adapter\AbstractAdapter
    {
        /** @var Adapter\MemcachedAdapter|Adapter\RedisAdapter|Adapter\ApcuAdapter|null $adapter */
        $adapter = null;

        if (null === $adapter && \extension_loaded('redis')) {
            $redis = new \Redis();
            $redis->connect('127.0.0.1');

            $adapter = new Adapter\RedisAdapter($redis);
        }

        if (\extension_loaded('memcached')) {
            $memcached = new \Memcached();
            $memcached->addServer('127.0.0.1', 11211);

            $adapter = new Adapter\MemcachedAdapter($memcached);
        }

        if (null === $adapter && \extension_loaded('apcu')) {
            $adapter = new Adapter\ApcuAdapter();
        }

        return $adapter;
    }
}
