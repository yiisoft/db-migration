<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Support\Helper;

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
use Yiisoft\Db\Pgsql\Connection as PgSqlConnection;
use Yiisoft\Profiler\Profiler;
use Yiisoft\Profiler\ProfilerInterface;
use Yiisoft\Test\Support\Container\SimpleContainer;

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
                        '@runtime' => dirname(__DIR__, 2) . '/runtime',
                    ],
                ),
            ],
            static function (string $id) use (&$container): object {
                switch ($id) {
                    case ConnectionInterface::class:
                        return new PgSqlConnection(
                            'pgsql:host=127.0.0.1;port=5432;dbname=testdb;user=postgres;password=postgres',
                            $container->get(QueryCache::class),
                            $container->get(SchemaCache::class),
                        );

                    case PgSqlConnection::class:
                        return $container->get(ConnectionInterface::class);

                    default:
                        return ContainerHelper::get($container, $id);
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
