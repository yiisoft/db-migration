<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Migration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Sqlite\Connection as SqLiteConnection;
use Yiisoft\Db\Sqlite\Driver as SqLiteDriver;
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;
use Yiisoft\Files\FileHelper;
use Yiisoft\Test\Support\SimpleCache\MemorySimpleCache;
use Yiisoft\Db\Migration\DatabaseRegistry;
use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Db\Migration\Command\CreateCommand;
use Yiisoft\Db\Migration\Command\DownCommand;
use Yiisoft\Db\Migration\Command\HistoryCommand;
use Yiisoft\Db\Migration\Command\MarkCommand;
use Yiisoft\Db\Migration\Command\NewCommand;
use Yiisoft\Db\Migration\Command\RedoCommand;
use Yiisoft\Db\Migration\Command\UpdateCommand;
use Yiisoft\Db\Migration\Informer\ConsoleMigrationInformer;
use Yiisoft\Db\Migration\Informer\MigrationInformerInterface;
use Yiisoft\Db\Migration\Informer\NullMigrationInformer;
use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\Migrator;
use Yiisoft\Db\Migration\Runner\DownRunner;
use Yiisoft\Db\Migration\Runner\UpdateRunner;
use Yiisoft\Db\Migration\Service\Generate\CreateService;
use Yiisoft\Db\Migration\Service\MigrationService;

use function dirname;
use function file_get_contents;
use function glob;
use function str_contains;
use function sys_get_temp_dir;
use function uniqid;

final class ConfigTest extends TestCase
{
    public function testBase(): void
    {
        $container = $this->createConsoleContainer();

        // Commands
        $this->assertInstanceOf(CreateCommand::class, $container->get(CreateCommand::class));
        $this->assertInstanceOf(DownCommand::class, $container->get(DownCommand::class));
        $this->assertInstanceOf(HistoryCommand::class, $container->get(HistoryCommand::class));
        $this->assertInstanceOf(MarkCommand::class, $container->get(MarkCommand::class));
        $this->assertInstanceOf(NewCommand::class, $container->get(NewCommand::class));
        $this->assertInstanceOf(RedoCommand::class, $container->get(RedoCommand::class));
        $this->assertInstanceOf(UpdateCommand::class, $container->get(UpdateCommand::class));

        // Informer
        $this->assertInstanceOf(ConsoleMigrationInformer::class, $container->get(MigrationInformerInterface::class));
        $this->assertInstanceOf(NullMigrationInformer::class, $container->get(NullMigrationInformer::class));
        $this->assertInstanceOf(ConsoleMigrationInformer::class, $container->get(ConsoleMigrationInformer::class));

        // Runners
        $this->assertInstanceOf(DownRunner::class, $container->get(DownRunner::class));
        $this->assertInstanceOf(UpdateRunner::class, $container->get(UpdateRunner::class));

        // Services
        $this->assertInstanceOf(CreateService::class, $container->get(CreateService::class));
        $this->assertInstanceOf(MigrationService::class, $container->get(MigrationService::class));

        // Other
        $this->assertInstanceOf(MigrationBuilder::class, $container->get(MigrationBuilder::class));
        $this->assertInstanceOf(Migrator::class, $container->get(Migrator::class));
    }

    public function testAdditionalDatabaseConfiguration(): void
    {
        $container = $this->createConsoleContainer(
            [
                'maps' => [
                    'db' => 'db.maps',
                    'newMigrationPath' => dirname(__DIR__) . '/Support/MigrationsExtra',
                    'historyTable' => 'maps_history',
                ],
            ],
            [
                'db.maps' => [
                    'class' => SqLiteConnection::class,
                    '__construct()' => [
                        'driver' => new SqLiteDriver('sqlite::memory:'),
                    ],
                ],
            ],
        );
        $this->assertSame(['maps'], $container->get(DatabaseRegistry::class)->getNames());

        $command = new CommandTester($container->get(DownCommand::class));
        $this->assertSame(2, $command->execute([]));
        $this->assertStringContainsString('--db option is required', $command->getDisplay());

        $command = new CommandTester($container->get(NewCommand::class));
        $this->assertSame(0, $command->execute(['--db' => 'maps']));
        $this->assertStringContainsString('M231108183919Empty', $command->getDisplay());
        $this->assertNotNull(
            $container->get('db.maps')->getSchema()->getTableSchema('maps_history'),
        );
    }

    public static function transactionModes(): array
    {
        return [
            ['create', null, false],
            ['table', null, true],
            ['create', true, true],
            ['table', false, false],
        ];
    }

    #[DataProvider('transactionModes')]
    public function testTransactionModeConfiguration(string $command, ?bool $transactional, bool $expected): void
    {
        $container = $this->createConsoleContainer(transactional: $transactional);

        $code = $container->get(CreateService::class)->run($command, 'post', 'Example');

        $this->assertSame($expected, str_contains($code, 'TransactionalMigrationInterface'));
    }

    #[DataProvider('transactionModes')]
    public function testNamedDatabaseTransactionModeConfiguration(
        string $command,
        ?bool $transactional,
        bool $expected,
    ): void {
        $directory = sys_get_temp_dir() . '/yii-migration-config-' . uniqid();
        $container = $this->createConsoleContainer([
            'maps' => [
                'db' => ConnectionInterface::class,
                'newMigrationPath' => $directory,
                'newMigrationTransactional' => $transactional,
            ],
        ]);

        try {
            FileHelper::ensureDirectory($directory);
            $tester = new CommandTester($container->get(CreateCommand::class));
            $exitCode = $tester->execute(['name' => 'post', '--command' => $command, '--db' => 'maps']);

            $this->assertSame(0, $exitCode, $tester->getDisplay());
            $files = glob($directory . '/*.php');
            $this->assertCount(1, $files);
            $code = file_get_contents($files[0]);
            $this->assertSame($expected, str_contains($code, 'TransactionalMigrationInterface'));
        } finally {
            FileHelper::removeDirectory($directory);
        }
    }

    private function createConsoleContainer(
        array $databases = [],
        array $definitions = [],
        ?bool $transactional = null,
    ): Container {
        $config = ContainerConfig::create()
            ->withDefinitions(array_merge(
                [
                    CacheInterface::class => MemorySimpleCache::class,

                    ConnectionInterface::class => [
                        'class' => SqLiteConnection::class,
                        '__construct()' => [
                            'driver' => new SqLiteDriver(
                                'sqlite:' . dirname(__DIR__, 2) . '/runtime/tests/config-test.sq3',
                            ),
                        ],
                    ],
                ],
                $definitions,
                $this->getConsoleDefinitions($databases, $transactional),
            ));
        return new Container($config);
    }

    private function getConsoleDefinitions(array $databases, ?bool $transactional): array
    {
        $params = $this->getParams();
        $params['yiisoft/db-migration']['databases'] = $databases;
        $params['yiisoft/db-migration']['newMigrationTransactional'] = $transactional;
        return require dirname(__DIR__, 2) . '/config/di-console.php';
    }

    private function getParams(): array
    {
        return require dirname(__DIR__, 2) . '/config/params.php';
    }
}
