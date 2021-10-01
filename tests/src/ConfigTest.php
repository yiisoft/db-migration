<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests;

use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Sqlite\Connection as SqlLiteConnection;
use Yiisoft\Definitions\Reference;
use Yiisoft\Di\Container;
use Yiisoft\EventDispatcher\Dispatcher\Dispatcher;
use Yiisoft\EventDispatcher\Provider\Provider;
use Yiisoft\Yii\Db\Migration\Informer\MigrationInformerInterface;
use Yiisoft\Yii\Db\Migration\Informer\NullMigrationInformer;
use Yiisoft\Yii\Db\Migration\Service\Generate\CreateService;
use Yiisoft\Yii\Db\Migration\Service\MigrationService;

final class ConfigTest extends TestCase
{
    public function testBase(): void
    {
        $container = $this->createConsoleContainer();

        $this->assertInstanceOf(MigrationService::class, $container->get(MigrationService::class));
        $this->assertInstanceOf(NullMigrationInformer::class, $container->get(MigrationInformerInterface::class));
        $this->assertInstanceOf(CreateService::class, $container->get(CreateService::class));
    }

    private function createConsoleContainer(): Container
    {
        $params = $this->getParams();
        return new Container(
            array_merge(
                [
                    Aliases::class => [
                        '__construct()' => [
                            $params['yiisoft/aliases']['aliases'],
                        ],
                    ],

                    CacheInterface::class => [
                        'class' => Cache::class,
                        '__construct()' => [Reference::to(ArrayCache::class)],
                    ],

                    ListenerProviderInterface::class => Provider::class,
                    EventDispatcherInterface::class => Dispatcher::class,

                    ConnectionInterface::class => [
                        'class' => SqlLiteConnection::class,
                        '__construct()' => [
                            'dsn' => 'sqlite:' . dirname(__DIR__, 2) . '/runtime/config-test.sq3',
                        ],
                    ],
                ],
                $this->getConsoleDefinitions()
            )
        );
    }

    private function getConsoleDefinitions(): array
    {
        $params = $this->getParams();
        return require dirname(__DIR__, 2) . '/config/console.php';
    }

    private function getParams(): array
    {
        return require dirname(__DIR__, 2) . '/config/params.php';
    }
}
