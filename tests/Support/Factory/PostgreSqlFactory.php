<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Support\Factory;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Pgsql\Connection as PgSqlConnection;
use Yiisoft\Db\Pgsql\Driver as PgSqlDriver;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Test\Support\SimpleCache\MemorySimpleCache;
use Yiisoft\Db\Migration\Tests\Support\Helper\ContainerConfig;
use Yiisoft\Db\Migration\Tests\Support\Helper\ContainerHelper;

use function dirname;

final class PostgreSqlFactory
{
    public static function createContainer(?ContainerConfig $config = null): ContainerInterface
    {
        $config ??= new ContainerConfig();

        $container = new SimpleContainer(
            [
                LoggerInterface::class => new NullLogger(),
                SchemaCache::class => new SchemaCache(new MemorySimpleCache()),
                Aliases::class => new Aliases(
                    [
                        '@runtime' => dirname(__DIR__, 2) . '/runtime',
                    ],
                ),
            ],
            static function (string $id) use (&$container, $config): object {
                return match ($id) {
                    ConnectionInterface::class => new PgSqlConnection(
                        new PgSqlDriver(
                            'pgsql:host=127.0.0.1;port=5432;dbname=yiitest',
                            'root',
                            'root',
                        ),
                        new SchemaCache(new MemorySimpleCache()),
                    ),
                    PgSqlConnection::class => $container->get(ConnectionInterface::class),
                    default => ContainerHelper::get($container, $id, $config),
                };
            }
        );

        return $container;
    }

    public static function clearDatabase(ContainerInterface $container): void
    {
        $db = $container->get(PgSqlConnection::class);

        foreach ($db->getSchema()->getSchemaNames(true) as $name) {
            $db
                ->createCommand('drop schema ' . $db->getQuoter()->quoteTableName($name) . ' cascade')
                ->execute();
        }

        self::createSchema($container, 'public');
    }

    public static function createSchema(ContainerInterface $container, string $name): void
    {
        /** @var ConnectionInterface $db */
        $db = $container->get(ConnectionInterface::class);

        $quotedName = $db->getQuoter()->quoteTableName($name);

        $db->createCommand('drop schema if exists ' . $quotedName)->execute();
        $db->createCommand('create schema ' . $quotedName)->execute();
    }
}
