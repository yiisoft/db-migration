<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\CommandLoader\ContainerCommandLoader;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Sqlite\Connection as SqliteConnection;
use Yiisoft\Di\Container;
use Yiisoft\EventDispatcher\Dispatcher\Dispatcher;
use Yiisoft\EventDispatcher\Provider\Provider;
use Yiisoft\Factory\Definitions\Reference;
use Yiisoft\Files\FileHelper;
use Yiisoft\View\WebView;
use Yiisoft\Yii\Db\Migration\Command\CreateCommand;
use Yiisoft\Yii\Db\Migration\Command\DownCommand;
use Yiisoft\Yii\Db\Migration\Command\HistoryCommand;
use Yiisoft\Yii\Db\Migration\Command\ListTablesCommand;
use Yiisoft\Yii\Db\Migration\Command\NewCommand;
use Yiisoft\Yii\Db\Migration\Command\RedoCommand;
use Yiisoft\Yii\Db\Migration\Command\UpdateCommand;
use Yiisoft\Yii\Db\Migration\Helper\ConsoleHelper;
use Yiisoft\Yii\Db\Migration\Service\MigrationService;

use function closedir;
use function is_dir;
use function opendir;
use function str_replace;

abstract class TestCase extends BaseTestCase
{
    private ContainerInterface $container;
    protected Application $application;
    protected Aliases $aliases;
    protected ?ConnectionInterface $db = null;
    protected ConsoleHelper $consoleHelper;
    protected Migration $migration;
    protected MigrationService $migrationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configContainer();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->aliases, $this->application, $this->container, $this->migrationService);
    }

    protected function configContainer(): void
    {
        $this->container = new Container($this->config());

        $this->container->get(ListenerProviderInterface::class);
        $this->container->get(EventDispatcherInterface::class);
        $this->application = $this->container->get(Application::class);
        $this->aliases = $this->container->get(Aliases::class);
        $this->consoleHelper = $this->container->get(ConsoleHelper::class);
        $this->db = $this->container->get(ConnectionInterface::class);
        $this->migration = $this->container->get(Migration::class);
        $this->migrationService = $this->container->get(MigrationService::class);

        $loader = new ContainerCommandLoader(
            $this->container,
            [
                'generate/create' => CreateCommand::class,
                'database/list' => ListTablesCommand::class,
                'migrate/down' => DownCommand::class,
                'migrate/history' => HistoryCommand::class,
                'migrate/new' => NewCommand::class,
                'migrate/redo' => RedoCommand::class,
                'migrate/up' => UpdateCommand::class
            ]
        );

        $this->application->setCommandLoader($loader);
    }

    /**
     * Asserting two strings equality ignoring line endings.
     *
     * @param string $expected
     * @param string $actual
     * @param string $message
     *
     * @return void
     */
    protected function assertEqualsWithoutLE(string $expected, string $actual, string $message = ''): void
    {
        $expected = str_replace("\r\n", "\n", $expected);
        $actual = str_replace("\r\n", "\n", $actual);

        $this->assertEquals($expected, $actual, $message);
    }

    protected function removeFiles(string $basePath): void
    {
        $handle = opendir($dir = $this->aliases->get($basePath));

        if ($handle === false) {
            throw new Exception("Unable to open directory: $dir");
        }

        while (($file = readdir($handle)) !== false) {
            if (in_array($file, ['.', '..', '.gitignore', '.gitkeep'], true)) {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                FileHelper::removeDirectory($path);
            } else {
                FileHelper::unlink($path);
            }
        }

        closedir($handle);
    }

    private function config(): array
    {
        return [
            Aliases::class => [
                '@root' => dirname(__DIR__, 1),
                '@yiisoft/yii/db/migration' => dirname(__DIR__, 1)
            ],

            Cache::class => [
                '__class' => Cache::class,
                '__construct()' => [Reference::to(ArrayCache::class)]
            ],

            CacheInterface::class => Cache::class,

            ListenerProviderInterface::class => Provider::class,

            EventDispatcherInterface::class => Dispatcher::class,

            LoggerInterface::class => NullLogger::class,

            ConnectionInterface::class => [
                '__class' => SqliteConnection::class,
                '__construct()' => [
                    'dsn' => 'sqlite:' . __DIR__ . '/Data/yiitest.sq3'
                ]
            ],

            WebView::class => [
                '__class' => WebView::class,
                '__construct()' => [
                    'basePath' => dirname(__DIR__) . '/resources/views'
                ]
            ]
        ];
    }
}
