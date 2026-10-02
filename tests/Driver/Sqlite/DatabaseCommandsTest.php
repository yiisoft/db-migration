<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Driver\Sqlite;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Yiisoft\Db\Exception\Exception as DbException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Db\Migration\Command\CreateCommand;
use Yiisoft\Db\Migration\Command\CommandFactory;
use Yiisoft\Db\Migration\Command\DownCommand;
use Yiisoft\Db\Migration\Command\HistoryCommand;
use Yiisoft\Db\Migration\Command\NewCommand;
use Yiisoft\Db\Migration\Command\RedoCommand;
use Yiisoft\Db\Migration\Command\UpdateCommand;
use Yiisoft\Db\Migration\DatabaseSet;
use Yiisoft\Db\Migration\DatabaseSetRegistry;
use Yiisoft\Db\Migration\Informer\NullMigrationInformer;
use Yiisoft\Db\Migration\Migrator;
use Yiisoft\Db\Migration\Runner\DownRunner;
use Yiisoft\Db\Migration\Runner\UpdateRunner;
use Yiisoft\Db\Migration\Service\Generate\CreateService;
use Yiisoft\Db\Migration\Service\MigrationService;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Test\Support\SimpleCache\MemorySimpleCache;
use Yiisoft\Db\Sqlite\Connection;
use Yiisoft\Db\Sqlite\Driver;
use Yiisoft\Files\FileHelper;
use Yiisoft\Injector\Injector;
use Yiisoft\Db\Migration\Tests\Support\MigrationsExtra\M231108183919Empty;
use Yiisoft\Db\Migration\Tests\Support\MigrationsExtra\M231108183919Empty2;

use function dirname;

final class DatabaseCommandsTest extends TestCase
{
    private string $directory;
    /** @var array<string, Connection> */
    private array $connections;
    /** @var array<string, DatabaseSet> */
    private array $sets;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/yii-migration-databases-' . uniqid();
        $this->connections = [];
        $this->sets = [];
        foreach (['default', 'maps', 'analytics'] as $name) {
            FileHelper::ensureDirectory($this->directory . '/' . $name);
            $this->connections[$name] = new Connection(new Driver('sqlite::memory:'), new SchemaCache(new MemorySimpleCache()));
            $this->sets[$name] = new DatabaseSet(
                $this->connections[$name],
                newMigrationPath: $this->directory . '/' . $name,
            );
        }
    }

    protected function tearDown(): void
    {
        FileHelper::removeDirectory($this->directory);
    }

    public function testApplyAllInConfigurationOrderWithIndependentHistory(): void
    {
        $classes = [];
        foreach ($this->sets as $name => $set) {
            $classes[$name] = $this->writeMigration($name);
        }
        // Even when "default" was not inserted first, it must run first.
        $this->sets = ['analytics' => $this->sets['analytics'], 'default' => $this->sets['default'], 'maps' => $this->sets['maps']];
        $command = $this->command('up');
        self::assertSame(Command::SUCCESS, $command->execute(['--force-yes' => true]));
        foreach ($classes as $name => $class) {
            self::assertSame([$class], array_keys($this->migrator($name)->getHistory()));
            self::assertNotNull($this->connections[$name]->getSchema()->getTableSchema('example'));
        }
        $output = $command->getDisplay();
        self::assertLessThan(strpos($output, 'Database: analytics'), strpos($output, 'Database: default'));
        self::assertLessThan(strpos($output, 'Database: maps'), strpos($output, 'Database: analytics'));
        // Already applied migrations must not run again.
        self::assertSame(Command::SUCCESS, $command->execute(['--force-yes' => true]));
    }

    public function testSelectDatabaseForUpDownAndRedo(): void
    {
        foreach (['default', 'maps', 'analytics'] as $name) {
            $this->writeMigration($name);
        }
        self::assertSame(Command::SUCCESS, $this->command('up')->execute(['--db' => 'maps', '--force-yes' => true]));
        self::assertNull($this->connections['default']->getSchema()->getTableSchema('migration'));
        self::assertNull($this->connections['analytics']->getSchema()->getTableSchema('migration'));
        self::assertCount(1, $this->migrator('maps')->getHistory());
        self::assertSame(Command::SUCCESS, $this->command('redo')->execute(['--db' => 'maps', '--force-yes' => true]));
        self::assertCount(1, $this->migrator('maps')->getHistory());
        self::assertNotNull($this->connections['maps']->getSchema()->getTableSchema('example', true));
        self::assertSame(Command::SUCCESS, $this->command('down')->execute(['--db' => 'maps', '--force-yes' => true]));
        self::assertSame([], $this->migrator('maps')->getHistory());
        self::assertNull($this->connections['maps']->getSchema()->getTableSchema('example', true));
    }

    #[DataProvider('defaultSelection')]
    public function testNamedDefaultSuppliesConnectionSourcesAndHistory(bool $selectDefault): void
    {
        $class = $this->writeMigration('maps');
        $default = new DatabaseSet(
            $this->connections['maps'],
            newMigrationPath: $this->directory . '/maps',
            historyTable: 'custom_history',
        );
        unset($this->sets['maps'], $this->sets['analytics']);
        $input = $selectDefault ? ['--db' => 'default'] : [];
        $new = $this->command('new', $default);
        self::assertSame(Command::SUCCESS, $new->execute($input));
        self::assertStringContainsString($class, $new->getDisplay());
        self::assertSame(Command::SUCCESS, $this->command('up', $default)->execute($input + ['--force-yes' => true]));
        $history = $this->command('history', $default);
        self::assertSame(Command::SUCCESS, $history->execute($input));
        self::assertStringContainsString($class, $history->getDisplay());
        self::assertNotNull($this->connections['maps']->getSchema()->getTableSchema('custom_history'));
        self::assertNull($this->connections['maps']->getSchema()->getTableSchema('migration'));
        self::assertSame(Command::SUCCESS, $this->command('redo', $default)->execute($input + ['--force-yes' => true]));
        self::assertNotNull($this->connections['maps']->getSchema()->getTableSchema('example', true));
        self::assertSame(Command::SUCCESS, $this->command('down', $default)->execute($input + ['--force-yes' => true]));
        self::assertNull($this->connections['maps']->getSchema()->getTableSchema('example', true));
        self::assertSame(Command::SUCCESS, $this->command('create', $default)->execute($input + ['name' => 'CustomDefault']));
        self::assertCount(1, glob($this->directory . '/maps/*CustomDefault.php'));
        self::assertSame([], glob($this->directory . '/default/*.php'));
        self::assertSame([], $this->connections['default']->getSchema()->getTableNames());
    }

    public static function defaultSelection(): array
    {
        return [[false], [true]];
    }

    public static function commands(): array
    {
        return [['up'], ['down'], ['redo'], ['new'], ['history'], ['create']];
    }

    #[DataProvider('commands')]
    public function testUnknownDatabaseIsRejectedBeforeAnyWork(string $name): void
    {
        $command = $this->command($name);
        $input = ['--db' => 'unknown'];
        if ($name === 'create') {
            $input['name'] = 'Example';
        }
        self::assertSame(Command::INVALID, $command->execute($input));
        self::assertStringContainsString('Unknown database "unknown". Available databases: default, maps, analytics.', preg_replace('/\s+/', ' ', $command->getDisplay()));
        foreach ($this->connections as $connection) {
            self::assertNull($connection->getSchema()->getTableSchema('migration'));
        }
    }

    public static function destructiveCommands(): array
    {
        return [['down'], ['redo']];
    }

    #[DataProvider('destructiveCommands')]
    public function testDestructiveCommandsRequireDatabaseWithMultipleSets(string $name): void
    {
        $command = $this->command($name);
        self::assertSame(Command::INVALID, $command->execute(['--force-yes' => true]));
        self::assertStringContainsString('--db option is required', $command->getDisplay());
        foreach ($this->connections as $connection) {
            self::assertNull($connection->getSchema()->getTableSchema('migration'));
        }
    }

    #[DataProvider('destructiveCommands')]
    public function testDestructiveCommandsDoNotRequireDatabaseWithOneSet(string $name): void
    {
        unset($this->sets['maps'], $this->sets['analytics']);
        $this->writeMigration('default');
        self::assertSame(Command::SUCCESS, $this->command('up')->execute(['--force-yes' => true]));
        self::assertSame(Command::SUCCESS, $this->command($name)->execute(['--force-yes' => true]));
    }

    public static function sourceOverrides(): array
    {
        return [
            ['up', '--path'],
            ['up', '--namespace'],
            ['new', '--path'],
            ['new', '--namespace'],
        ];
    }

    #[DataProvider('sourceOverrides')]
    public function testSourceOverrideRequiresDatabaseBeforeAnyWork(string $name, string $option): void
    {
        $this->writeMigration('analytics');
        $input = [$option => [$option === '--path'
            ? $this->directory . '/analytics'
            : 'Yiisoft\\Db\\Migration\\Tests\\Support\\MigrationsExtra']];
        if ($name === 'up') {
            $input['--force-yes'] = true;
        }
        $command = $this->command($name);
        self::assertSame(Command::INVALID, $command->execute($input));
        self::assertStringContainsString(
            'The --db option is required with --path or --namespace',
            preg_replace('/\\s+/', ' ', $command->getDisplay()),
        );
        foreach ($this->connections as $connection) {
            self::assertSame([], $connection->getSchema()->getTableNames());
        }
    }

    #[DataProvider('sourceOverrides')]
    public function testSourceOverrideWorksWithExplicitDatabase(string $name, string $option): void
    {
        $class = $this->writeMigration('analytics');
        $input = ['--db' => 'analytics', $option => [$option === '--path'
            ? $this->directory . '/analytics'
            : 'Yiisoft\\Db\\Migration\\Tests\\Support\\MigrationsExtra']];
        if ($name === 'up') {
            $input['--force-yes'] = true;
        }
        $command = $this->command($name);
        self::assertSame(Command::SUCCESS, $command->execute($input));
        self::assertStringContainsString($class, $command->getDisplay());
        self::assertSame([], $this->connections['default']->getSchema()->getTableNames());
        self::assertSame([], $this->connections['maps']->getSchema()->getTableNames());
        if ($name === 'up') {
            self::assertArrayHasKey($class, $this->migrator('analytics')->getHistory());
        }
    }

    #[DataProvider('sourceOverrides')]
    public function testSourceOverrideWorksWithoutDatabaseWithOneSet(string $name, string $option): void
    {
        unset($this->sets['maps'], $this->sets['analytics']);
        $class = $this->writeMigration('default');
        $input = [$option => [$option === '--path'
            ? $this->directory . '/default'
            : 'Yiisoft\\Db\\Migration\\Tests\\Support\\MigrationsExtra']];
        if ($name === 'up') {
            $input['--force-yes'] = true;
        }
        $command = $this->command($name);
        self::assertSame(Command::SUCCESS, $command->execute($input));
        self::assertStringContainsString($class, $command->getDisplay());
    }

    public function testCreateDefaultsToDefaultAndCanSelectAnotherSet(): void
    {
        $command = $this->command('create');
        self::assertSame(Command::SUCCESS, $command->execute(['name' => 'DefaultExample']));
        self::assertCount(1, glob($this->directory . '/default/*.php'));
        self::assertSame([], glob($this->directory . '/maps/*.php'));
        self::assertSame(Command::SUCCESS, $command->execute(['name' => 'MapsExample', '--db' => 'maps']));
        self::assertCount(1, glob($this->directory . '/maps/*.php'));
        self::assertSame([], glob($this->directory . '/analytics/*.php'));
    }

    public function testStopsOnExceptionAndRollsBackOnlyTheFailingDatabase(): void
    {
        $defaultClass = $this->writeMigration('default');
        $this->writeMigration('maps', fail: true);
        $this->writeMigration('analytics');
        try {
            $this->command('up')->execute(['--force-yes' => true]);
            self::fail('An exception must propagate.');
        } catch (RuntimeException $exception) {
            self::assertSame('Migration failed.', $exception->getMessage());
        }
        self::assertSame([$defaultClass], array_keys($this->migrator('default')->getHistory()));
        self::assertNotNull($this->connections['default']->getSchema()->getTableSchema('example'));
        self::assertSame([], $this->migrator('maps')->getHistory());
        self::assertNull($this->connections['maps']->getSchema()->getTableSchema('example', true));
        self::assertNull($this->connections['analytics']->getSchema()->getTableSchema('migration'));
    }

    public function testStopsOnNonzeroExitCode(): void
    {
        $this->writeMigration('default');
        $this->sets['maps'] = new DatabaseSet($this->connections['maps']);
        $this->writeMigration('analytics');
        self::assertSame(Command::INVALID, $this->command('up')->execute(['--force-yes' => true]));
        self::assertCount(1, $this->migrator('default')->getHistory());
        self::assertNull($this->connections['analytics']->getSchema()->getTableSchema('migration'));
    }

    public static function listingCommands(): array
    {
        return [['new'], ['history']];
    }

    #[DataProvider('listingCommands')]
    public function testListingContinuesPastEmptySetsAndCanSelectOne(string $name): void
    {
        $class = $this->writeMigration('maps');
        if ($name === 'history') {
            $this->command('up')->execute(['--db' => 'maps', '--force-yes' => true]);
        }
        $command = $this->command($name);
        self::assertSame(Command::SUCCESS, $command->execute([]));
        self::assertStringContainsString($class, $command->getDisplay());
        self::assertStringContainsString('Database: analytics', $command->getDisplay());
        self::assertSame(Command::SUCCESS, $command->execute(['--db' => 'maps']));
        self::assertStringNotContainsString('Database: default', $command->getDisplay());
        // Explicit selection keeps the original empty-result exit code.
        self::assertSame(Command::FAILURE, $command->execute(['--db' => 'default']));
    }

    #[DataProvider('listingCommands')]
    public function testListingAllEmptySetsFailsAfterVisitingEverySet(string $name): void
    {
        $command = $this->command($name);
        self::assertSame(Command::FAILURE, $command->execute([]));
        foreach (['default', 'maps', 'analytics'] as $database) {
            self::assertStringContainsString('Database: ' . $database, $command->getDisplay());
        }
    }

    #[DataProvider('listingCommands')]
    public function testListingSucceedsWhenOnlyTheLastSetHasResults(string $name): void
    {
        $class = $this->writeMigration('analytics');
        if ($name === 'history') {
            $this->command('up')->execute(['--db' => 'analytics', '--force-yes' => true]);
        }
        $command = $this->command($name);
        self::assertSame(Command::SUCCESS, $command->execute([]));
        self::assertStringContainsString($class, $command->getDisplay());
    }

    #[DataProvider('listingCommands')]
    public function testListingAllNonemptySetsSucceeds(string $name): void
    {
        $classes = [];
        foreach (['default', 'maps', 'analytics'] as $database) {
            $classes[] = $this->writeMigration($database);
        }
        if ($name === 'history') {
            $this->command('up')->execute(['--force-yes' => true]);
        }
        $command = $this->command($name);
        self::assertSame(Command::SUCCESS, $command->execute([]));
        foreach ($classes as $class) {
            self::assertStringContainsString($class, $command->getDisplay());
        }
    }

    #[DataProvider('listingCommands')]
    public function testListingStopsOnErrorEvenAfterFindingResults(string $name): void
    {
        $this->writeMigration('default');
        if ($name === 'history') {
            $this->command('up')->execute(['--db' => 'default', '--force-yes' => true]);
        }
        $this->sets['maps'] = new DatabaseSet(
            new Connection(
                new Driver('sqlite:' . $this->directory . '/missing/maps.sqlite'),
                new SchemaCache(new MemorySimpleCache()),
            ),
            newMigrationPath: $this->directory . '/maps',
        );
        $command = $this->command($name);
        try {
            $command->execute([]);
            self::fail('The database error must propagate.');
        } catch (DbException) {
            self::assertStringContainsString('Database: maps', $command->getDisplay());
            self::assertStringNotContainsString('Database: analytics', $command->getDisplay());
        }
        self::assertSame([], $this->connections['analytics']->getSchema()->getTableNames());
    }

    #[DataProvider('listingCommands')]
    public function testListingRejectsInvalidOptionsBeforeVisitingLaterSets(string $name): void
    {
        $command = $this->command($name);
        self::assertSame(Command::INVALID, $command->execute(['--limit' => 0]));
        self::assertStringNotContainsString('Database: maps', $command->getDisplay());
        self::assertStringNotContainsString('Database: analytics', $command->getDisplay());
    }

    public function testLimitAppliesToEachDatabase(): void
    {
        foreach (['default', 'maps', 'analytics'] as $name) {
            $this->writeMigration($name, 'first');
            $this->writeMigration($name, 'second');
        }
        self::assertSame(Command::SUCCESS, $this->command('up')->execute(['--limit' => 1, '--force-yes' => true]));
        foreach (['default', 'maps', 'analytics'] as $name) {
            self::assertCount(1, $this->migrator($name)->getHistory());
        }
    }

    public function testSetsCanShareAConnectionWithSeparateHistoryTables(): void
    {
        $this->connections['maps'] = $this->connections['default'];
        $this->sets['maps'] = new DatabaseSet(
            $this->connections['maps'],
            newMigrationPath: $this->directory . '/maps',
            historyTable: 'maps_history',
        );
        $defaultClass = $this->writeMigration('default', 'main_example');
        $mapsClass = $this->writeMigration('maps', 'maps_example');
        self::assertSame(Command::SUCCESS, $this->command('up')->execute(['--force-yes' => true]));
        self::assertSame([$defaultClass], array_keys($this->migrator('default')->getHistory()));
        self::assertSame([$mapsClass], array_keys($this->migrator('maps')->getHistory()));
    }

    public function testNamedSetUsesItsConnectionForGeneration(): void
    {
        $this->connections['default']->createCommand('CREATE TABLE parent (default_key INTEGER PRIMARY KEY)')->execute();
        $this->connections['maps']->createCommand('CREATE TABLE parent (maps_key INTEGER PRIMARY KEY)')->execute();
        $this->sets['maps'] = new DatabaseSet(
            $this->connections['maps'],
            newMigrationPath: $this->directory . '/maps',
            useTablePrefix: false,
        );
        self::assertSame(Command::SUCCESS, $this->command('create')->execute([
            'name' => 'child',
            '--command' => 'table',
            '--fields' => 'parent_id:integer:foreignKey(parent)',
            '--db' => 'maps',
        ]));
        $files = glob($this->directory . '/maps/*.php');
        self::assertCount(1, $files);
        $content = file_get_contents($files[0]);
        self::assertStringContainsString("'maps_key'", $content);
        self::assertStringNotContainsString("'default_key'", $content);
        self::assertStringNotContainsString('{{%parent}}', $content);
    }

    public function testCreateUsesNamedSetMigrationNameLimit(): void
    {
        $this->sets['maps'] = new DatabaseSet(
            $this->connections['maps'],
            newMigrationPath: $this->directory . '/maps',
            migrationNameLimit: 5,
        );
        self::assertSame(Command::INVALID, $this->command('create')->execute([
            'name' => 'Example',
            '--db' => 'maps',
        ]));
        self::assertSame([], glob($this->directory . '/maps/*.php'));
    }

    public function testNamedSetDefaultGenerationSettings(): void
    {
        $command = $this->command('create');
        self::assertSame(Command::SUCCESS, $command->execute([
            'name' => str_repeat('a', 167),
            '--db' => 'maps',
            '--command' => 'create',
        ]));
        self::assertSame(Command::INVALID, $command->execute([
            'name' => str_repeat('a', 168),
            '--db' => 'maps',
        ]));
        self::assertSame(Command::SUCCESS, $command->execute([
            'name' => 'child',
            '--command' => 'table',
            '--fields' => 'parent_id:integer:foreignKey(parent)',
            '--db' => 'maps',
        ]));
        $files = glob($this->directory . '/maps/*CreateChildTable.php');
        self::assertCount(1, $files);
        self::assertStringContainsString('{{%parent}}', file_get_contents($files[0]));
    }

    public function testNamedSetDiscoversMigrationsInItsNewMigrationNamespace(): void
    {
        $this->sets['maps'] = new DatabaseSet(
            $this->connections['maps'],
            newMigrationNamespace: 'Yiisoft\Db\Migration\Tests\Support\MigrationsExtra',
        );
        $command = $this->command('new');
        self::assertSame(Command::SUCCESS, $command->execute(['--db' => 'maps']));
        self::assertStringContainsString(M231108183919Empty::class, $command->getDisplay());
    }

    public function testNamedSetRespectsItsMigrationNameLimit(): void
    {
        $this->writeMigration('maps');
        $this->sets['maps'] = new DatabaseSet(
            $this->connections['maps'],
            newMigrationPath: $this->directory . '/maps',
            migrationNameLimit: 5,
        );
        self::assertSame(Command::INVALID, $this->command('up')->execute(['--db' => 'maps', '--force-yes' => true]));
        self::assertSame([], $this->migrator('maps')->getHistory());
        self::assertNull($this->connections['maps']->getSchema()->getTableSchema('example'));
    }

    public function testNamedSetsRetainMultipleMigrationSources(): void
    {
        $class = $this->writeMigration('maps');
        $extraClass = $this->writeMigration('analytics', 'extra_example');
        $this->sets['maps'] = new DatabaseSet(
            $this->connections['maps'],
            newMigrationPath: $this->directory . '/maps',
            sourceNamespaces: ['Yiisoft\\Db\\Migration\\Tests\\Support\\MigrationsExtra'],
            sourcePaths: [$this->directory . '/analytics', dirname(__DIR__, 2) . '/Support/MigrationsExtra2'],
        );
        self::assertSame(Command::SUCCESS, $this->command('up')->execute(['--db' => 'maps', '--force-yes' => true]));
        $history = $this->migrator('maps')->getHistory();
        self::assertArrayHasKey($class, $history);
        self::assertArrayHasKey($extraClass, $history);
        self::assertArrayHasKey(M231108183919Empty::class, $history);
        self::assertArrayHasKey(M231108183919Empty2::class, $history);
        self::assertNull($this->connections['analytics']->getSchema()->getTableSchema('extra_example'));
    }

    public function testNamedSetCommandOptionsDoNotLeakIntoLaterRuns(): void
    {
        $mapsClass = $this->writeMigration('maps');
        $extraClass = $this->writeMigration('analytics');
        $command = $this->command('new');
        self::assertSame(Command::SUCCESS, $command->execute(['--db' => 'maps', '--path' => [$this->directory . '/analytics']]));
        self::assertStringContainsString($mapsClass, $command->getDisplay());
        self::assertStringContainsString($extraClass, $command->getDisplay());
        self::assertSame(Command::SUCCESS, $command->execute(['--db' => 'maps']));
        self::assertStringContainsString($mapsClass, $command->getDisplay());
        self::assertStringNotContainsString($extraClass, $command->getDisplay());
    }

    private function writeMigration(string $database, string $table = 'example', bool $fail = false): string
    {
        $class = 'M260930000000' . ucfirst($database) . uniqid();
        $failure = $fail ? "throw new \\RuntimeException('Migration failed.');" : '';
        file_put_contents($this->directory . '/' . $database . '/' . $class . '.php', <<<PHP
            <?php
            use Yiisoft\Db\Migration\MigrationBuilder;
            use Yiisoft\Db\Migration\RevertibleMigrationInterface;
            use Yiisoft\Db\Migration\TransactionalMigrationInterface;
            use Yiisoft\Db\Schema\Column\ColumnBuilder;
            final class $class implements RevertibleMigrationInterface, TransactionalMigrationInterface
            {
                public function up(MigrationBuilder \$b): void
                {
                    \$b->createTable('$table', ['id' => ColumnBuilder::primaryKey()]);
                    $failure
                }
                public function down(MigrationBuilder \$b): void
                {
                    \$b->dropTable('$table');
                }
            }
            PHP);
        return $class;
    }

    private function migrator(string $name): Migrator
    {
        return new Migrator($this->connections[$name], new NullMigrationInformer(), $this->sets[$name]->historyTable);
    }

    private function command(string $name, ?DatabaseSet $default = null): CommandTester
    {
        $sets = $this->sets;
        unset($sets['default']);
        if ($default !== null) {
            $sets['default'] = $default;
        }
        $registry = new DatabaseSetRegistry(new Injector(), new NullMigrationInformer(), $sets);
        if ($default !== null) {
            return new CommandTester((new CommandFactory($registry))->create($name));
        }
        $migrator = $this->migrator('default');
        $service = new MigrationService($this->connections['default'], new Injector(), $migrator);
        $service->setNewMigrationPath($this->sets['default']->newMigrationPath);
        $command = match ($name) {
            'up' => new UpdateCommand(new UpdateRunner($migrator), $service, $migrator, $registry),
            'down' => new DownCommand(new DownRunner($migrator), $service, $migrator, $registry),
            'redo' => new RedoCommand($service, $migrator, new DownRunner($migrator), new UpdateRunner($migrator), $registry),
            'new' => new NewCommand($service, $migrator, $registry),
            'history' => new HistoryCommand($service, $migrator, $registry),
            'create' => new CreateCommand(new CreateService($this->connections['default']), $service, $migrator, $registry),
        };
        return new CommandTester($command);
    }
}
