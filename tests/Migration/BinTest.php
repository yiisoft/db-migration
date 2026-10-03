<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Migration;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Yiisoft\Files\FileHelper;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Sqlite\Connection;
use Yiisoft\Db\Sqlite\Driver;
use Yiisoft\Test\Support\SimpleCache\MemorySimpleCache;

use function dirname;

final class BinTest extends TestCase
{
    protected function setUp(): void
    {
        FileHelper::copyFile(
            dirname(__DIR__, 2) . '/bin/yii-db-migration',
            dirname(__DIR__) . '/runtime/bin/vendor/yiisoft/db-migration/bin/yii-db-migration',
        );
        FileHelper::copyFile(
            dirname(__DIR__, 2) . '/bin/yii-db-migration.php',
            dirname(__DIR__) . '/runtime/bin/yii-db-migration.php',
        );
    }

    protected function tearDown(): void
    {
        FileHelper::removeDirectory(dirname(__DIR__) . '/runtime/bin');
    }

    public function testBase(): void
    {
        $this->replaceParams("'databases' => [],", '');
        $this->replaceParams(
            "'db' => null,",
            <<<'PHP'
            'db' => new \Yiisoft\Db\Sqlite\Connection(
                new \Yiisoft\Db\Sqlite\Driver('sqlite::memory:'),
                new \Yiisoft\Db\Cache\SchemaCache(new \Yiisoft\Test\Support\SimpleCache\MemorySimpleCache())
            ),
            PHP,
        );

        [$output, $exitCode] = $this->runYiiDbMigration();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Yii Database Migration Tool', $output);
        $this->assertStringContainsString('migrate:mark', $output);
    }

    public function testWithoutConnection(): void
    {
        [$output, $exitCode] = $this->runYiiDbMigration();

        $this->assertSame(255, $exitCode);
        $this->assertStringContainsString('LogicException: DB connection is not configured.', $output);
    }

    public function testWithoutConfig(): void
    {
        unlink(dirname(__DIR__) . '/runtime/bin/yii-db-migration.php');

        [$output, $exitCode] = $this->runYiiDbMigration();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('not found', $output);
        $this->assertStringContainsString(
            'cp "' . dirname(__DIR__) . '/runtime/bin/vendor/yiisoft/db-migration/bin/yii-db-migration.php" "'
            . dirname(__DIR__) . '/runtime/bin/yii-db-migration.php"',
            $output,
        );
    }

    public function testNamedDatabase(): void
    {
        $this->replaceParams("'db' => null,", <<<'PHP'
            'db' => new \Yiisoft\Db\Sqlite\Connection(
                new \Yiisoft\Db\Sqlite\Driver('sqlite:' . __DIR__ . '/default.sqlite'),
                new \Yiisoft\Db\Cache\SchemaCache(new \Yiisoft\Test\Support\SimpleCache\MemorySimpleCache())
            ),
            PHP);
        $this->replaceParams("'databases' => [],", <<<'PHP'
            'databases' => [
                'maps' => new \Yiisoft\Db\Migration\DatabaseSet(
                    new \Yiisoft\Db\Sqlite\Connection(
                        new \Yiisoft\Db\Sqlite\Driver('sqlite:' . __DIR__ . '/maps.sqlite'),
                        new \Yiisoft\Db\Cache\SchemaCache(new \Yiisoft\Test\Support\SimpleCache\MemorySimpleCache())
                    ),
                    newMigrationPath: __DIR__ . '/maps',
                    historyTable: 'maps_history',
                ),
            ],
            PHP);
        $directory = dirname(__DIR__) . '/runtime/bin';
        FileHelper::ensureDirectory($directory . '/maps');
        file_put_contents($directory . '/maps/M260930000000Maps.php', <<<'PHP'
            <?php
            final class M260930000000Maps implements \Yiisoft\Db\Migration\MigrationInterface
            {
                public function up(\Yiisoft\Db\Migration\MigrationBuilder $b): void
                {
                    $b->execute('CREATE TABLE example (id INTEGER)');
                }
            }
            PHP);
        [$output, $exitCode] = $this->runYiiDbMigration(['migrate:up', '--db=maps', '--force-yes']);
        $this->assertSame(0, $exitCode, $output);
        $this->assertStringContainsString('Database: maps', $output);
        $this->assertFileDoesNotExist($directory . '/default.sqlite');
        $db = new Connection(
            new Driver('sqlite:' . $directory . '/maps.sqlite'),
            new SchemaCache(new MemorySimpleCache()),
        );
        $this->assertSame('M260930000000Maps', $db->createCommand('SELECT name FROM maps_history')->queryScalar());
        $this->assertSame(0, (int) $db->createCommand('SELECT COUNT(*) FROM example')->queryScalar());
    }

    public function testNamedDefaultWithoutLegacyOptions(): void
    {
        $directory = dirname(__DIR__) . '/runtime/bin';
        FileHelper::ensureDirectory($directory . '/migrations');
        file_put_contents($directory . '/yii-db-migration.php', $this->namedDefaultConfig());
        [$output, $exitCode] = $this->runYiiDbMigration(['migrate:create', 'Example']);
        self::assertSame(0, $exitCode, $output);
        self::assertCount(1, glob($directory . '/migrations/*Example.php'));
        [$output, $exitCode] = $this->runYiiDbMigration(['migrate:up', '-y']);
        self::assertSame(0, $exitCode, $output);
        $db = new Connection(new Driver('sqlite:' . $directory . '/named.sqlite'), new SchemaCache(new MemorySimpleCache()));
        self::assertCount(1, $db->createCommand('SELECT name FROM named_history')->queryColumn());
    }

    public static function legacyOptions(): array
    {
        return array_map(static fn(string $option): array => [$option], [
            'db', 'newMigrationNamespace', 'newMigrationPath', 'sourceNamespaces', 'sourcePaths',
            'historyTable', 'migrationNameLimit', 'useTablePrefix', 'maxSqlOutputLength',
        ]);
    }

    #[DataProvider('legacyOptions')]
    public function testRejectsDuplicateDefaultConfiguration(string $option): void
    {
        $directory = dirname(__DIR__) . '/runtime/bin';
        file_put_contents($directory . '/yii-db-migration.php', str_replace(
            'return [',
            "return ['$option' => null,",
            $this->namedDefaultConfig(),
        ));
        [$output, $exitCode] = $this->runYiiDbMigration(['migrate:up', '-y']);
        self::assertNotSame(0, $exitCode);
        self::assertStringContainsString('Conflicting option: ' . $option . '.', $output);
        self::assertFileDoesNotExist($directory . '/named.sqlite');
    }

    private function namedDefaultConfig(): string
    {
        return <<<'PHP'
            <?php
            return [
                'databases' => [
                    'default' => new \Yiisoft\Db\Migration\DatabaseSet(
                        new \Yiisoft\Db\Sqlite\Connection(
                            new \Yiisoft\Db\Sqlite\Driver('sqlite:' . __DIR__ . '/named.sqlite'),
                            new \Yiisoft\Db\Cache\SchemaCache(new \Yiisoft\Test\Support\SimpleCache\MemorySimpleCache())
                        ),
                        newMigrationPath: __DIR__ . '/migrations',
                        historyTable: 'named_history',
                    ),
                ],
            ];
            PHP;
    }

    private function replaceParams($search, $replace): void
    {
        $file = dirname(__DIR__) . '/runtime/bin/yii-db-migration.php';
        file_put_contents(
            $file,
            str_replace($search, $replace, file_get_contents($file)),
        );
    }

    private function runYiiDbMigration(array $arguments = []): array
    {
        exec(__DIR__ . '/bin-runner.php ' . implode(' ', array_map(escapeshellarg(...), $arguments)) . ' 2>&1', $output, $exitCode);
        return [implode("\n", $output), $exitCode];
    }
}
