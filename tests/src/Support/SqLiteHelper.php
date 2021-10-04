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
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Sqlite\Connection as SqlLiteConnection;
use Yiisoft\Test\Support\Container\SimpleContainer;

use function dirname;

final class SqLiteHelper
{
    public static function createContainer(): ContainerInterface
    {
        $container = new SimpleContainer(
            [
                LoggerInterface::class => new NullLogger(),
                CacheInterface::class => new Cache(new ArrayCache()),
                Aliases::class => new Aliases(
                    [
                        '@runtime' => dirname(__DIR__, 2) . '/runtime',
                    ],
                ),
            ],
            static function (string $id) use (&$container): object {
                switch ($id) {
                    case ConnectionInterface::class:
                        return new SqlLiteConnection(
                            'sqlite:' . dirname(__DIR__, 2) . '/runtime/testdb.sq3',
                            $container->get(QueryCache::class),
                            $container->get(SchemaCache::class),
                        );

                    case SqlLiteConnection::class:
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
        $db = $container->get(SqlLiteConnection::class);
        foreach ($db->getSchema()->getTableNames() as $tableName) {
            $db->createCommand()->dropTable($tableName)->execute();
        }
    }
}
