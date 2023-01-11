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
use Yiisoft\Db\Sqlite\ConnectionPDO as SqLiteConnection;
use Yiisoft\Db\Sqlite\PDODriver as SqLitePDODriver;
use Yiisoft\Test\Support\Container\SimpleContainer;

use function dirname;

final class SqLiteHelper
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
                        return new SqLiteConnection(
                            new SqLitePDODriver(
                                'sqlite:' . dirname(__DIR__, 3) . '/runtime/testdb.sq3'
                            ),
                            $container->get(SchemaCache::class),
                        );

                    case SqLiteConnection::class:
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
        $db = $container->get(SqLiteConnection::class);
        foreach ($db->getSchema()->getTableNames() as $tableName) {
            $db->createCommand()->dropTable($tableName)->execute();
        }
    }
}
