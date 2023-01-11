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
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Pgsql\ConnectionPDO as PgSqlConnection;
use Yiisoft\Db\Pgsql\PDODriver as PgSqlPDODriver;
use Yiisoft\Test\Support\Container\SimpleContainer;

use function dirname;

final class PostgreSqlHelper
{
    public static function createContainer(?ContainerConfig $config = null): ContainerInterface
    {
        $container = new SimpleContainer(
            [
                LoggerInterface::class => new NullLogger(),
                CacheInterface::class => new Cache(new ArrayCache()),
                Aliases::class => new Aliases(
                    [
                        '@runtime' => dirname(__DIR__, 3) . '/runtime',
                    ],
                ),
            ],
            static function (string $id) use (&$container, $config): object {
                switch ($id) {
                    case ConnectionInterface::class:
                        return new PgSqlConnection(
                            new PgSqlPDODriver(
                                'pgsql:host=127.0.0.1;port=5432;dbname=testdb',
                                'postgres',
                                'postgres',
                            ),
                            $container->get(SchemaCache::class),
                        );

                    case PgSqlConnection::class:
                        return $container->get(ConnectionInterface::class);

                    default:
                        return ContainerHelper::get($container, $id, $config ?? new ContainerConfig());
                }
            }
        );
        return $container;
    }

    public static function clearDatabase(ContainerInterface $container): void
    {
        $connection = $container->get(PgSqlConnection::class);
        foreach ($connection->getSchema()->getSchemaNames(true) as $name) {
            $connection
                ->createCommand('drop schema ' . $connection->getQuoter()->quoteTableName($name) . ' cascade')
                ->execute();
        }
        self::createSchema($container, 'public');
    }

    public static function createSchema(ContainerInterface $container, string $name): void
    {
        /** @var ConnectionInterface $connection */
        $connection = $container->get(ConnectionInterface::class);

        $quotedName = $connection->getQuoter()->quoteTableName($name);

        $connection->createCommand('drop schema if exists ' . $quotedName)->execute();
        $connection->createCommand('create schema ' . $quotedName)->execute();
    }
}
