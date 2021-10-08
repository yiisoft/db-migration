<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Support\Helper;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Db\Cache\QueryCache;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\EventDispatcher\Dispatcher\Dispatcher;
use Yiisoft\EventDispatcher\Provider\ListenerCollection;
use Yiisoft\EventDispatcher\Provider\Provider;
use Yiisoft\Injector\Injector;
use Yiisoft\Test\Support\Container\Exception\NotFoundException;
use Yiisoft\Yii\Db\Migration\Command\CreateCommand;
use Yiisoft\Yii\Db\Migration\Command\DownCommand;
use Yiisoft\Yii\Db\Migration\Command\NewCommand;
use Yiisoft\Yii\Db\Migration\Command\UpdateCommand;
use Yiisoft\Yii\Db\Migration\Informer\ConsoleMigrationInformer;
use Yiisoft\Yii\Db\Migration\Migrator;
use Yiisoft\Yii\Db\Migration\Runner\DownRunner;
use Yiisoft\Yii\Db\Migration\Runner\UpdateRunner;
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

            case UpdateRunner::class:
                return new UpdateRunner(
                    $container->get(Migrator::class),
                );

            case DownRunner::class:
                return new DownRunner(
                    $container->get(Migrator::class),
                );

            case Migrator::class:
                return new Migrator(
                    $container->get(ConnectionInterface::class),
                    $container->get(SchemaCache::class),
                    $container->get(QueryCache::class),
                    $container->get(ConsoleMigrationInformer::class),
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

            case ConsoleMigrationInformer::class:
                return new ConsoleMigrationInformer();

            case CreateCommand::class:
                return new CreateCommand(
                    $container->get(Aliases::class),
                    $container->get(CreateService::class),
                    $container->get(MigrationService::class),
                    $container->get(Migrator::class),
                );

            case UpdateCommand::class:
                return new UpdateCommand(
                    $container->get(UpdateRunner::class),
                    $container->get(MigrationService::class),
                    $container->get(Migrator::class),
                    $container->get(ConsoleMigrationInformer::class),
                );

            case DownCommand::class:
                return new DownCommand(
                    $container->get(DownRunner::class),
                    $container->get(MigrationService::class),
                    $container->get(Migrator::class),
                    $container->get(ConsoleMigrationInformer::class),
                );

            case NewCommand::class:
                return new NewCommand(
                    $container->get(MigrationService::class),
                );

            default:
                throw new NotFoundException($id);
        }
    }
}
