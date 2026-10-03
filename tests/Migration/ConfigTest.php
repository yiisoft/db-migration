<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Migration;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use LogicException;
use Psr\SimpleCache\CacheInterface;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Sqlite\Connection as SqLiteConnection;
use Yiisoft\Db\Sqlite\Driver as SqLiteDriver;
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;
use Yiisoft\Test\Support\SimpleCache\MemorySimpleCache;
use Yiisoft\Db\Migration\DatabaseSet;
use Yiisoft\Db\Migration\DatabaseSetRegistry;
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
        $set = new DatabaseSet(new SqLiteConnection(new SqLiteDriver('sqlite::memory:'), new SchemaCache(new MemorySimpleCache())));
        $container = $this->createConsoleContainer(['maps' => $set]);
        $this->assertSame(['default', 'maps'], $container->get(DatabaseSetRegistry::class)->getNames());
        $command = new CommandTester($container->get(DownCommand::class));
        $this->assertSame(2, $command->execute([]));
        $this->assertStringContainsString('--db option is required', $command->getDisplay());
    }

    public function testNamedDefaultNeedsNoLegacyConnection(): void
    {
        $db = new SqLiteConnection(new SqLiteDriver('sqlite::memory:'), new SchemaCache(new MemorySimpleCache()));
        $container = new Container(ContainerConfig::create()->withDefinitions($this->getConsoleDefinitions([
            'default' => new DatabaseSet($db, newMigrationPath: dirname(__DIR__) . '/Support/MigrationsExtra'),
        ])));
        foreach ([CreateCommand::class, DownCommand::class, HistoryCommand::class, MarkCommand::class, NewCommand::class, RedoCommand::class, UpdateCommand::class] as $class) {
            self::assertInstanceOf($class, $container->get($class));
        }
        $command = new CommandTester($container->get(NewCommand::class));
        self::assertSame(0, $command->execute([]));
        self::assertStringContainsString('M231108183919Empty', $command->getDisplay());
    }

    public static function legacyMigrationOptions(): array
    {
        return [['newMigrationNamespace'], ['newMigrationPath'], ['sourceNamespaces'], ['sourcePaths']];
    }

    #[DataProvider('legacyMigrationOptions')]
    public function testRejectsDuplicateDefaultConfiguration(string $option): void
    {
        $params = $this->getParams();
        $params['yiisoft/db-migration']['databases']['default'] = new DatabaseSet(
            new SqLiteConnection(new SqLiteDriver('sqlite::memory:'), new SchemaCache(new MemorySimpleCache())),
        );
        $params['yiisoft/db-migration'][$option] = '';
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Conflicting option: ' . $option . '.');
        require dirname(__DIR__, 2) . '/config/di-console.php';
    }

    private function createConsoleContainer(array $databases = []): Container
    {
        $config = ContainerConfig::create()
            ->withDefinitions(array_merge(
                [
                    CacheInterface::class => MemorySimpleCache::class,

                    ConnectionInterface::class => [
                        'class' => SqLiteConnection::class,
                        '__construct()' => [
                            'driver' => new SqLiteDriver(
                                'sqlite:' . dirname(__DIR__, 2) . '/runtime/config-test.sq3',
                            ),
                        ],
                    ],
                ],
                $this->getConsoleDefinitions($databases),
            ));
        return new Container($config);
    }

    private function getConsoleDefinitions(array $databases): array
    {
        $params = $this->getParams();
        $params['yiisoft/db-migration']['databases'] = $databases;
        return require dirname(__DIR__, 2) . '/config/di-console.php';
    }

    private function getParams(): array
    {
        return require dirname(__DIR__, 2) . '/config/params.php';
    }
}
