<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface as SimpleCacheInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Db\Connection\Connection;
use Yiisoft\Log\Logger;
use Yiisoft\Profiler\Profiler;

return [
    SimpleCacheInterface::class => ArrayCache::class,
    CacheInterface::class => Cache::class,
    LoggerInterface::class => Logger::class,

    Connection::class => function (ContainerInterface $container) {
        $aliases = $container->get(Aliases::class);
        $cache = $container->get(CacheInterface::class);
        $logger = $container->get(LoggerInterface::class);
        $profiler = $container->get(Profiler::class);

        $db = new Connection(
            $cache,
            $logger,
            $profiler,
            'sqlite:' . $aliases->get('@yiisoft/yii/db/migration/runtime') . '/yiitest.sq3'
        );

        return $db;
    },
];
