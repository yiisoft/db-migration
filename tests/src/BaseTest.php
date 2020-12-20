<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests;

use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Sqlite\Connection as SqlLiteConnection;
use Yiisoft\Di\Container;
use Yiisoft\EventDispatcher\Dispatcher\Dispatcher;
use Yiisoft\EventDispatcher\Provider\Provider;
use Yiisoft\Factory\Definitions\Reference;
use Yiisoft\View\WebView;
use Yiisoft\Yii\Db\Migration\Service\Generate\CreateService;
use Yiisoft\Yii\Db\Migration\Service\Migrate\UpdateService;
use Yiisoft\Yii\Db\Migration\Service\MigrationService;

abstract class BaseTest extends TestCase
{
    public const DB_FILE = __DIR__ . '/../runtime/yiitest.sq3';

    private ?Container $container = null;

    protected function tearDown(): void
    {
        parent::tearDown();

        $db = $this->getDb();
        foreach ($db->getSchema()->getTableNames() as $tableName) {
            $db->createCommand()->dropTable($tableName)->execute();
        }

        unset($this->container);
    }

    protected function getContainer(): Container
    {
        if ($this->container === null) {
            $this->container = new Container([
                Aliases::class => [
                    '@root' => dirname(__DIR__, 2),
                    '@runtime' => dirname(__DIR__) . '/runtime',
                    '@yiisoft/yii/db/migration' => '@root',
                ],

                CacheInterface::class => [
                    '__class' => Cache::class,
                    '__construct()' => [Reference::to(ArrayCache::class)],
                ],

                ListenerProviderInterface::class => Provider::class,

                EventDispatcherInterface::class => Dispatcher::class,

                LoggerInterface::class => NullLogger::class,

                ConnectionInterface::class => [
                    '__class' => SqlLiteConnection::class,
                    '__construct()' => [
                        'dsn' => 'sqlite:' . self::DB_FILE,
                    ],
                ],

                WebView::class => [
                    '__class' => WebView::class,
                    '__construct()' => [
                        'basePath' => '@root/resources/views',
                    ],
                ],
            ]);
        }
        return $this->container;
    }

    protected function getAliases(): Aliases
    {
        return $this->getContainer()->get(Aliases::class);
    }

    protected function getDb(): SqlLiteConnection
    {
        return $this->getContainer()->get(ConnectionInterface::class);
    }

    protected function getMigrationService(): MigrationService
    {
        return $this->getContainer()->get(MigrationService::class);
    }

    protected function getCreateService(): CreateService
    {
        return $this->getContainer()->get(CreateService::class);
    }

    protected function getUpdateService(): UpdateService
    {
        return $this->getContainer()->get(UpdateService::class);
    }

    protected function getParams(): array
    {
        return include dirname(__DIR__, 2) . '/config/params.php';
    }

    protected function createTable(string $table, array $columns): void
    {
        $this->getDb()
            ->createCommand()
            ->createTable($table, $columns)
            ->execute();
    }

    protected function createMigration(
        string $name,
        string $command,
        string $table,
        array $fields = []
    ): void {
        $migrationService = $this->getMigrationService();

        list($namespace, $className) = $migrationService->generateClassName(null, $name);

        $content = $this->getCreateService()->run(
            $command,
            $migrationService->getGeneratorTemplateFiles($command),
            $table,
            $className,
            $namespace,
            $fields
        );

        $path = $this->getAliases()->get($migrationService->findMigrationPath($namespace));
        $fileName = $className . '.php';

        file_put_contents($path . '/' . $fileName, $content);
    }

    protected function applyNewMigrations(): void
    {
        foreach ($this->getMigrationService()->getNewMigrations() as $migration) {
            $this->getUpdateService()->run($migration);
        }
    }

    /**
     * Asserting two strings equality ignoring line endings.
     *
     * @param string $expected
     * @param string $actual
     * @param string $message
     */
    protected function assertEqualsWithoutLE(string $expected, string $actual, string $message = ''): void
    {
        $expected = str_replace("\r\n", "\n", $expected);
        $actual = str_replace("\r\n", "\n", $actual);

        $this->assertEquals($expected, $actual, $message);
    }

    protected function assertExistsTables(string ...$tables): void
    {
        $existsTables = $this->getDb()->getSchema()->getTableNames();
        foreach ($tables as $table) {
            $this->assertTrue(in_array($table, $existsTables));
        }
    }

    protected function assertNotExistsTables(string ...$tables): void
    {
        $existsTables = $this->getDb()->getSchema()->getTableNames();
        foreach ($tables as $table) {
            $this->assertFalse(in_array($table, $existsTables));
        }
    }
}
