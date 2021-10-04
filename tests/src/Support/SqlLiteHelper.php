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
use Yiisoft\Injector\Injector;
use Yiisoft\Test\Support\Container\Exception\NotFoundException;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Yii\Db\Migration\Informer\ConsoleMigrationInformer;
use Yiisoft\Yii\Db\Migration\Migrator;

use Yiisoft\Yii\Db\Migration\Service\MigrationService;

use function dirname;

final class SqlLiteHelper
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
        $db = $container->get(SqlLiteConnection::class);
        foreach ($db->getSchema()->getTableNames() as $tableName) {
            $db->createCommand()->dropTable($tableName)->execute();
        }
    }
}
