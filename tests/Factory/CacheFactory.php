<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineOrmFilter\Test\Factory;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\Psr6\DoctrineProvider;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;

final class CacheFactory
{
    public static function create(): Cache
    {
        /** @var MemcachedAdapter|RedisAdapter|ApcuAdapter|ArrayAdapter|null $adapter */
        $adapter = null;
        if (\extension_loaded('memcached')) {
            $memcached = new \Memcached();
            $memcached->addServer('127.0.0.1', 11211);

            $adapter = new MemcachedAdapter($memcached);
        }

        if (null === $adapter && \extension_loaded('redis')) {
            $redis = new \Redis();
            $redis->connect('127.0.0.1');

            $adapter = new RedisAdapter($redis);
        }

        if (null === $adapter && \extension_loaded('apcu')) {
            $adapter = new ApcuAdapter();
        }

        if (null === $adapter) {
            $adapter = new ArrayAdapter();
        }

        return DoctrineProvider::wrap($adapter);
    }
}
