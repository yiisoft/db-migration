<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Support;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Db\Cache\QueryCache;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Pgsql\Connection as PgSqlConnection;
use Yiisoft\EventDispatcher\Dispatcher\Dispatcher;
use Yiisoft\EventDispatcher\Provider\ListenerCollection;
use Yiisoft\EventDispatcher\Provider\Provider;
use Yiisoft\Injector\Injector;
use Yiisoft\Test\Support\Container\Exception\NotFoundException;
use Yiisoft\Yii\Db\Migration\Command\CreateCommand;
use Yiisoft\Yii\Db\Migration\Informer\ConsoleMigrationInformer;
use Yiisoft\Yii\Db\Migration\Migrator;
use Yiisoft\Yii\Db\Migration\Service\Generate\CreateService;
use Yiisoft\Yii\Db\Migration\Service\MigrationService;

final class ContainerHelper
{
    /**
     * @throws NotFoundException
     */
    public static function get(ContainerInterface $container, string $id): object
    {
        switch ($id) {
            case SchemaCache::class:
                return new SchemaCache($container->get(CacheInterface::class));

            case QueryCache::class:
                return new QueryCache($container->get(CacheInterface::class));

            case Injector::class:
                return new Injector($container);

            case EventDispatcherInterface::class:
                return new Dispatcher(
                    new Provider(new ListenerCollection())
                );

            case Migrator::class:
                return new Migrator(
                    $container->get(ConnectionInterface::class),
                    $container->get(SchemaCache::class),
                    $container->get(QueryCache::class),
                    new ConsoleMigrationInformer(),
                );

            case MigrationService::class:
                return new MigrationService(
                    $container->get(Aliases::class),
                    $container->get(ConnectionInterface::class),
                    $container->get(Injector::class),
                    $container->get(Migrator::class),
                );

            case CreateService::class:
                return new CreateService(
                    $container->get(Aliases::class),
                    $container->get(ConnectionInterface::class),
                    $container->get(MigrationService::class),
                    $container->get(EventDispatcherInterface::class),
                );

            case CreateCommand::class:
                return new CreateCommand(
                    $container->get(Aliases::class),
                    $container->get(CreateService::class),
                    $container->get(MigrationService::class),
                    $container->get(Migrator::class),
                );

            default:
                throw new NotFoundException($id);
        }
    }
}
