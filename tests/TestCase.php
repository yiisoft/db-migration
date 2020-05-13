<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\CommandLoader\ContainerCommandLoader;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Composer\Config\Builder;
use Yiisoft\Di\Container;
use Yiisoft\Files\FileHelper;
use Yiisoft\Yii\Db\Migration\Command\CreateCommand;
use Yiisoft\Yii\Db\Migration\Command\ListTableCommand;
use Yiisoft\Yii\Db\Migration\Command\DownCommand;
use Yiisoft\Yii\Db\Migration\Command\HistoryCommand;
use Yiisoft\Yii\Db\Migration\Command\NewCommand;
use Yiisoft\Yii\Db\Migration\Command\RedoCommand;
use Yiisoft\Yii\Db\Migration\Command\ToCommand;
use Yiisoft\Yii\Db\Migration\Command\UpdateCommand;

abstract class TestCase extends BaseTestCase
{
    protected Application $application;
    protected Aliases $aliases;
    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configContainer();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->removeFiles($this->aliases->get('@migration'));

        unset($this->aliases, $this->application, $this->container);
    }

    protected function configContainer(): void
    {
        $config = require Builder::path('tests');

        $this->container = new Container($config);

        $this->application = $this->container->get(Application::class);
        $this->aliases = $this->container->get(Aliases::class);

        $loader = new \Symfony\Component\Console\CommandLoader\ContainerCommandLoader(
            $this->container,
            [
                'generate/create' => CreateCommand::class,
                'database/list' => ListTableCommand::class,
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

    private function removeFiles(string $basePath): void
    {
        $handle = opendir($dir = $this->aliases->get($basePath));

        if ($handle === false) {
            throw new \Exception("Unable to open directory: $dir");
        }

        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..' || $file === '.gitignore') {
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
}
