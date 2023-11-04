<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Support\Factory;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Sqlite\Connection as SqLiteConnection;
use Yiisoft\Db\Sqlite\Driver as SqLiteDriver;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Test\Support\SimpleCache\MemorySimpleCache;
use Yiisoft\Db\Migration\Tests\Support\Helper\ContainerConfig;
use Yiisoft\Db\Migration\Tests\Support\Helper\ContainerHelper;

use function dirname;

final class SqLiteFactory
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
                    ConnectionInterface::class => new SqLiteConnection(
                        new SqLiteDriver(
                            'sqlite:' . dirname(__DIR__, 2) . '/runtime/yiitest.sq3'
                        ),
                        new SchemaCache(new MemorySimpleCache())
                    ),
                    SqLiteConnection::class => $container->get(ConnectionInterface::class),
                    default => ContainerHelper::get($container, $id, $config),
                };
            }
        );

        return $container;
    }

    public static function clearDatabase(ContainerInterface $container): void
    {
        $db = $container->get(SqLiteConnection::class);
        $command = $db->createCommand();

        foreach ($db->getSchema()->getTableNames() as $tableName) {
            $command->dropTable($tableName)->execute();
        }

        $db->close();
    }
}
