<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests;

use Psr\Container\ContainerInterface;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\CommandLoader\ContainerCommandLoader;
use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Composer\Config\Builder;
use Yiisoft\Db\Connection\Connection;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Di\Container;
use Yiisoft\Files\FileHelper;
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
    protected Application $application;
    protected Aliases $aliases;
    protected Migration $migration;
    protected Connection $db;
    protected ConsoleHelper $consoleHelper;
    protected MigrationService $migrationService;
    private ContainerInterface $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configContainer();
        $this->db->beginTransaction();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->db->getTransaction()->rollBack();
        unset($this->aliases, $this->application, $this->container, $this->migrationService);
    }

    protected function getMigrationFolder()
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'migration' . DIRECTORY_SEPARATOR;
    }

    protected function getNamespaceMigrationFolder()
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'NamespaceMigrationGenerated' . DIRECTORY_SEPARATOR;
    }

    protected function getGeneratedMigrationFolder()
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'migrationGenerated' . DIRECTORY_SEPARATOR;
    }

    protected function configContainer(): void
    {
        $config = require Builder::path('tests');

        $this->container = new Container($config);

        $this->application = $this->container->get(Application::class);
        $this->aliases = $this->container->get(Aliases::class);
        $this->consoleHelper = $this->container->get(ConsoleHelper::class);
        $this->db = $this->container->get(Connection::class);
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
        $handle = opendir($dir = $basePath);

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

    protected function migrateUp(): void
    {
        $create = $this->application->find('migrate/up');

        $commandUp = new CommandTester($create);

        $commandUp->setInputs(['yes']);

        $commandUp->execute([]);
    }
}
