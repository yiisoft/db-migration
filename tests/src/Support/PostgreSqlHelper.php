<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Support;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Db\Cache\QueryCache;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\Connection;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Connection\LazyConnectionDependencies;
use Yiisoft\Db\Pgsql\Connection as PgSqlConnection;
use Yiisoft\Injector\Injector;
use Yiisoft\Profiler\Profiler;
use Yiisoft\Profiler\ProfilerInterface;
use Yiisoft\Test\Support\Container\Exception\NotFoundException;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Yii\Db\Migration\Informer\ConsoleMigrationInformer;
use Yiisoft\Yii\Db\Migration\Migrator;
use Yiisoft\Yii\Db\Migration\Service\MigrationService;

use function dirname;

final class PostgreSqlHelper
{
    public static function createContainer(): ContainerInterface
    {
        $container = new SimpleContainer(
            [
                LoggerInterface::class => new NullLogger(),
                CacheInterface::class => new Cache(new ArrayCache()),
                ProfilerInterface::class => new Profiler(new NullLogger()),
                Aliases::class => new Aliases(
                    [
                        '@root' => dirname(__DIR__, 3),
                        '@runtime' => dirname(__DIR__, 2) . '/runtime',
                        '@yiisoft/yii/db/migration' => '@root',
                    ],
                )
            ],
            static function (string $id) use (&$container): object {
                switch ($id) {
                    case LazyConnectionDependencies::class:
                        return new LazyConnectionDependencies($container);

                    case ConnectionInterface::class:
                        return new PgSqlConnection(
                            'pgsql:host=127.0.0.1;port=5432;dbname=testdb;user=postgres;password=postgres',
                            $container->get(LazyConnectionDependencies::class),
                        );

                    case PgSqlConnection::class:
                        return $container->get(ConnectionInterface::class);

                    case SchemaCache::class:
                        return new SchemaCache($container->get(CacheInterface::class));

                    case QueryCache::class:
                        return new QueryCache($container->get(CacheInterface::class));

                    case Injector::class:
                        return new Injector($container);

                    case Migrator::class:
                        return new Migrator(
                            $container->get(ConnectionInterface::class),
                            $container->get(SchemaCache::class),
                            $container->get(QueryCache::class),
                            new ConsoleMigrationInformer(),
                        );

                    case MigrationService::class:
                        return new  MigrationService(
                            $container->get(Aliases::class),
                            $container->get(ConnectionInterface::class),
                            $container->get(Injector::class),
                            $container->get(Migrator::class),
                        );

                    default:
                        throw new NotFoundException($id);
                }
            }
        );
        return $container;
    }

    public static function clearDatabase(ContainerInterface $container): void
    {
        $connection = $container->get(PgSqlConnection::class);
        foreach ($connection->getSchema()->getSchemaNames(true) as $name) {
            $connection->createCommand('drop schema ' . $connection->quoteTableName($name) . ' cascade')->execute();
        }
        self::createSchema($container, 'public');
    }

    public static function createSchema(ContainerInterface $container, string $name): void
    {
        /** @var Connection $connection */
        $connection = $container->get(ConnectionInterface::class);

        $quotedName = $connection->quoteTableName($name);

        $connection->createCommand('drop schema if exists ' . $quotedName)->execute();
        $connection->createCommand('create schema ' . $quotedName)->execute();
    }

    public static function createTable(ContainerInterface $container, string $table, array $columns): void
    {
        $connection = $container->get(PgSqlConnection::class);
        $connection->createCommand('drop table if exists ' . $connection->quoteTableName($table))->execute();
        $connection->createCommand()->createTable($table, $columns)->execute();
    }
}
