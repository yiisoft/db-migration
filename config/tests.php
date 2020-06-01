<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface as SimpleCacheInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Db\Connection\Connection;
use Yiisoft\Db\Connection\ConnectionPool;
use Yiisoft\EventDispatcher\Dispatcher\Dispatcher;
use Yiisoft\EventDispatcher\Provider\Provider;
use Yiisoft\Log\Logger;
use Yiisoft\Profiler\Profiler;

return [
    ContainerInterface::class => static function (ContainerInterface $container) {
        return $container;
    },

    ListenerProviderInterface::class => Provider::class,
    EventDispatcherInterface::class => Dispatcher::class,
    SimpleCacheInterface::class => ArrayCache::class,

    CacheInterface::class => Cache::class,

    LoggerInterface::class => Logger::class,

    Connection::class => static function (ContainerInterface $container) {
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

        $db->setUsername('root');
        $db->setPassword('root');

        ConnectionPool::setConnectionsPool('db', $db);

        return $db;
    },
];
