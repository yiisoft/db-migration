<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\CommandLoader\ContainerCommandLoader;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Sqlite\Connection as SqliteConnection;
use Yiisoft\Di\Container;
use Yiisoft\EventDispatcher\Dispatcher\Dispatcher;
use Yiisoft\EventDispatcher\Provider\Provider;
use Yiisoft\Files\FileHelper;
use Yiisoft\Log\Logger;
use Yiisoft\Profiler\Profiler;
use Yiisoft\View\Theme;
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
        $params = $this->params();

        return [
            Aliases::class => [
                '@root' => dirname(__DIR__, 1),
                '@data' => '@root/tests/Data',
                '@yiisoft/yii/db/migration' => dirname(__DIR__, 1),
                '@views' =>  '@yiisoft/yii/db/migration/resources/views'
            ],

            CacheInterface::class => static function (ContainerInterface $container) {
                return new Cache(new ArrayCache());
            },

            FileRotatorInterface::class => static function () {
                return new FileRotator(10);
            },

            ListenerProviderInterface::class => Provider::class,
            EventDispatcherInterface::class => Dispatcher::class,

            LoggerInterface::class => Logger::class,

            Profiler::class => static function (ContainerInterface $container) {
                return new Profiler($container->get(LoggerInterface::class));
            },

            ConnectionInterface::class => static function (ContainerInterface $container) use ($params) {
                $aliases = $container->get(Aliases::class);
                $cache = $container->get(CacheInterface::class);
                $logger = $container->get(LoggerInterface::class);
                $profiler = $container->get(Profiler::class);


                $db = new SqliteConnection(
                    $cache,
                    $logger,
                    $profiler,
                    'sqlite:' . $aliases->get('@data/yiitest.sq3')
                );

                return $db;
            },

            WebView::class => function (ContainerInterface $container) {
                $aliases = $container->get(Aliases::class);
                $eventDispatcher = $container->get(EventDispatcherInterface::class);
                $theme = $container->get(Theme::class);
                $logger = $container->get(LoggerInterface::class);

                return new WebView($aliases->get('@views'), $theme, $eventDispatcher, $logger);
            },
        ];
    }

    private function params(): array
    {
        return [
            'yiisoft/db-sqlite' => [
                'fixture' => __DIR__ . '/Data/sqlite.sql'
            ]
        ];
    }
}
