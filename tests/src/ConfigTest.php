<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Sqlite\ConnectionPDO as SqLiteConnection;
use Yiisoft\Db\Sqlite\PDODriver as SqLitePDODriver;
use Yiisoft\Definitions\Reference;
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;
use Yiisoft\Yii\Db\Migration\Command\CreateCommand;
use Yiisoft\Yii\Db\Migration\Command\DownCommand;
use Yiisoft\Yii\Db\Migration\Command\HistoryCommand;
use Yiisoft\Yii\Db\Migration\Command\ListTablesCommand;
use Yiisoft\Yii\Db\Migration\Command\NewCommand;
use Yiisoft\Yii\Db\Migration\Command\RedoCommand;
use Yiisoft\Yii\Db\Migration\Command\UpdateCommand;
use Yiisoft\Yii\Db\Migration\Informer\ConsoleMigrationInformer;
use Yiisoft\Yii\Db\Migration\Informer\MigrationInformerInterface;
use Yiisoft\Yii\Db\Migration\Informer\NullMigrationInformer;
use Yiisoft\Yii\Db\Migration\MigrationBuilder;
use Yiisoft\Yii\Db\Migration\Migrator;
use Yiisoft\Yii\Db\Migration\Runner\DownRunner;
use Yiisoft\Yii\Db\Migration\Runner\UpdateRunner;
use Yiisoft\Yii\Db\Migration\Service\Database\ListTablesService;
use Yiisoft\Yii\Db\Migration\Service\Generate\CreateService;
use Yiisoft\Yii\Db\Migration\Service\MigrationService;

use function dirname;

final class ConfigTest extends TestCase
{
    public function testBase(): void
    {
        $container = $this->createConsoleContainer();

        // Commands
        $this->assertInstanceOf(CreateCommand::class, $container->get(CreateCommand::class));
        $this->assertInstanceOf(DownCommand::class, $container->get(DownCommand::class));
        $this->assertInstanceOf(HistoryCommand::class, $container->get(HistoryCommand::class));
        $this->assertInstanceOf(ListTablesCommand::class, $container->get(ListTablesCommand::class));
        $this->assertInstanceOf(NewCommand::class, $container->get(NewCommand::class));
        $this->assertInstanceOf(RedoCommand::class, $container->get(RedoCommand::class));
        $this->assertInstanceOf(UpdateCommand::class, $container->get(UpdateCommand::class));

        // Informer
        $this->assertInstanceOf(NullMigrationInformer::class, $container->get(MigrationInformerInterface::class));
        $this->assertInstanceOf(NullMigrationInformer::class, $container->get(NullMigrationInformer::class));
        $this->assertInstanceOf(ConsoleMigrationInformer::class, $container->get(ConsoleMigrationInformer::class));

        // Runners
        $this->assertInstanceOf(DownRunner::class, $container->get(DownRunner::class));
        $this->assertInstanceOf(UpdateRunner::class, $container->get(UpdateRunner::class));

        // Services
        $this->assertInstanceOf(ListTablesService::class, $container->get(ListTablesService::class));
        $this->assertInstanceOf(CreateService::class, $container->get(CreateService::class));
        $this->assertInstanceOf(MigrationService::class, $container->get(MigrationService::class));

        // Other
        $this->assertInstanceOf(MigrationBuilder::class, $container->get(MigrationBuilder::class));
        $this->assertInstanceOf(Migrator::class, $container->get(Migrator::class));
    }

    private function createConsoleContainer(): Container
    {
        $config = ContainerConfig::create()
            ->withDefinitions(array_merge(
                [
                    CacheInterface::class => [
                        'class' => Cache::class,
                        '__construct()' => [Reference::to(ArrayCache::class)],
                    ],

                    ConnectionInterface::class => [
                        'class' => SqLiteConnection::class,
                        '__construct()' => [
                            'driver' => new SqLitePDODriver(
                                'sqlite:' . dirname(__DIR__, 2) . '/runtime/config-test.sq3'
                            ),
                        ],
                    ],
                ],
                $this->getConsoleDefinitions()
            ));
        return new Container($config);
    }

    private function getConsoleDefinitions(): array
    {
        $params = $this->getParams();
        return require dirname(__DIR__, 2) . '/config/di-console.php';
    }

    private function getParams(): array
    {
        return require dirname(__DIR__, 2) . '/config/params.php';
    }
}
