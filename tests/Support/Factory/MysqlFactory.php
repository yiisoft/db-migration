<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Support\Factory;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Mysql\Connection as MysqlConnection;
use Yiisoft\Db\Mysql\Driver as MysqlDriver;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Test\Support\SimpleCache\MemorySimpleCache;
use Yiisoft\Yii\Db\Migration\Tests\Support\Helper\ContainerConfig;
use Yiisoft\Yii\Db\Migration\Tests\Support\Helper\ContainerHelper;

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
                Aliases::class => new Aliases(
                    [
                        '@runtime' => dirname(__DIR__, 2) . '/runtime',
                    ],
                ),
            ],
            static function (string $id) use (&$container, $config): object {
                switch ($id) {
                    case ConnectionInterface::class:
                        return new MysqlConnection(
                            new MysqlDriver(
                                'mysql:host=127.0.0.1;dbname=yiitest;port=3306;charset=utf8',
                                'root',
                                '',
                            ),
                            new SchemaCache(new MemorySimpleCache()),
                        );

                    case MysqlConnection::class:
                        return $container->get(ConnectionInterface::class);

                    default:
                        return ContainerHelper::get($container, $id, $config);
                }
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
            'test_table',
            'target_table',
            'new_table',
        ];

        foreach ($tables as $table) {
            if ($db->getTableSchema($table)) {
                $db->createCommand('DROP TABLE IF EXISTS ' . $table .  ' CASCADE;')->execute();
            }
        }
    }
}
