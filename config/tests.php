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
use Yiisoft\Log\Target\File\FileRotator;
use Yiisoft\Log\Target\File\FileRotatorInterface;
use Yiisoft\Profiler\Profiler;

return [
    ContainerInterface::class => static function (ContainerInterface $container) {
        return $container;
    },

    Aliases::class => [
        '@root' => dirname(__DIR__, 1),
        '@runtime' => '@root/runtime',
        '@migration' => '@root/migration',
        '@views' => '@root/resources/views'
    ],

    ListenerProviderInterface::class => Provider::class,
    EventDispatcherInterface::class => Dispatcher::class,
    SimpleCacheInterface::class => ArrayCache::class,

    CacheInterface::class => Cache::class,

    FileRotatorInterface::class => [
        '__class' => FileRotator::class,
        '__construct()' => [
            10,
        ],
    ],

    LoggerInterface::class => Logger::class,

    Connection::class => static function (ContainerInterface $container) {
        $aliases = $container->get(Aliases::class);
        $cache = $container->get(CacheInterface::class);
        $logger = $container->get(LoggerInterface::class);
        $profiler = $container->get(Profiler::class);

        $db = new Connection($cache, $logger, $profiler, 'sqlite:' . $aliases->get('@runtime') . '/yiitest.sq3');

        $db->setUsername('root');
        $db->setPassword('root');

        ConnectionPool::setConnectionsPool('mysql', $db);

        return $db;
    },
];
