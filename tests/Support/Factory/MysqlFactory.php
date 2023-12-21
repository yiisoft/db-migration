<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Support\Factory;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Mysql\Connection as MysqlConnection;
use Yiisoft\Db\Mysql\Driver as MysqlDriver;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Test\Support\SimpleCache\MemorySimpleCache;
use Yiisoft\Db\Migration\Tests\Support\Helper\ContainerConfig;
use Yiisoft\Db\Migration\Tests\Support\Helper\ContainerHelper;

use function array_intersect;
use function dirname;

final class MysqlFactory
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
                    ConnectionInterface::class => new MysqlConnection(
                        new MysqlDriver(
                            'mysql:host=127.0.0.1;dbname=yiitest;port=3306;charset=utf8',
                            'root',
                            '',
                        ),
                        new SchemaCache(new MemorySimpleCache()),
                    ),
                    MysqlConnection::class => $container->get(ConnectionInterface::class),
                    default => ContainerHelper::get($container, $id, $config),
                };
            }
        );

        return $container;
    }

    public static function clearDatabase(ContainerInterface $container): void
    {
        $db = $container->get(MysqlConnection::class);

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
            'person',
            'book',
            'chapter',
        ];

        $tables = array_intersect($tables, $db->getSchema()->getTableNames());
        $command = $db->createCommand();

        foreach ($tables as $table) {
            $command->setSql('DROP TABLE IF EXISTS ' . $table . ' CASCADE')->execute();
        }

        $db->close();
    }
}
