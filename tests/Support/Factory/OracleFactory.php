<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Support\Factory;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Oracle\Connection as OracleConnection;
use Yiisoft\Db\Oracle\Driver as OracleDriver;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Test\Support\SimpleCache\MemorySimpleCache;
use Yiisoft\Db\Migration\Tests\Support\Helper\ContainerConfig;
use Yiisoft\Db\Migration\Tests\Support\Helper\ContainerHelper;

use function array_intersect;

final class OracleFactory
{
    public static function createContainer(?ContainerConfig $config = null): ContainerInterface
    {
        $config ??= new ContainerConfig();

        $container = new SimpleContainer(
            [
                LoggerInterface::class => new NullLogger(),
                SchemaCache::class => new SchemaCache(new MemorySimpleCache()),
            ],
            static function (string $id) use (&$container, $config): object {
                return match ($id) {
                    ConnectionInterface::class => new OracleConnection(
                        new OracleDriver(
                            'oci:dbname=localhost:1521/XE;charset=AL32UTF8',
                            'system',
                            'root',
                        ),
                        new SchemaCache(new MemorySimpleCache()),
                    ),
                    OracleConnection::class => $container->get(ConnectionInterface::class),
                    default => ContainerHelper::get($container, $id, $config),
                };
            }
        );

        return $container;
    }

    public static function clearDatabase(ContainerInterface $container): void
    {
        $db = $container->get(OracleConnection::class);

        $tables = [
            'migration',
            'student',
            'department',
            'post',
            'user',
            'tag',
            'category',
            'the_post',
            'the_user',
            'test',
            'test_pk',
            'test_table',
            'target_table',
            'new_table',
            'PERSON',
            'book',
            'chapter',
        ];

        $tables = array_intersect($tables, $db->getSchema()->getTableNames());
        $command = $db->createCommand();

        foreach ($tables as $table) {
            $command->setSql('DROP TABLE "' . $table . '" CASCADE CONSTRAINTS')->execute();
        }

        $db->close();
    }
}
