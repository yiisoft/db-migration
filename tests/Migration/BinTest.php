<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Migration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Files\FileHelper;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Sqlite\Connection;
use Yiisoft\Db\Sqlite\Driver;
use Yiisoft\Test\Support\SimpleCache\MemorySimpleCache;

use function dirname;
use function file_get_contents;
use function glob;
use function str_contains;
use function var_export;

final class BinTest extends TestCase
{
    protected function setUp(): void
    {
        FileHelper::copyFile(
            dirname(__DIR__, 2) . '/bin/yii-db-migration',
            dirname(__DIR__, 2) . '/runtime/tests/bin/vendor/yiisoft/db-migration/bin/yii-db-migration',
        );
        FileHelper::copyFile(
            dirname(__DIR__, 2) . '/bin/yii-db-migration.php',
            dirname(__DIR__, 2) . '/runtime/tests/bin/yii-db-migration.php',
        );
    }

    protected function tearDown(): void
    {
        FileHelper::removeDirectory(dirname(__DIR__, 2) . '/runtime/tests/bin');
    }

    public function testBase(): void
    {
        $this->replaceParams("'databases' => [],", '');
        $this->replaceParams("'newMigrationTransactional' => null,", '');
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
    }

    public static function transactionModes(): array
    {
        return [
            'configured on' => ['create', true, [], true],
            'configured off' => ['table', false, [], false],
            'override on' => ['create', false, ['--transaction'], true],
            'override off' => ['table', true, ['--no-transaction'], false],
        ];
    }

    #[DataProvider('transactionModes')]
    public function testCreateTransactionMode(string $command, bool $default, array $options, bool $expected): void
    {
        $this->replaceParams("'db' => null,", <<<'PHP'
            'db' => new \Yiisoft\Db\Sqlite\Connection(
                new \Yiisoft\Db\Sqlite\Driver('sqlite::memory:'),
                new \Yiisoft\Db\Cache\SchemaCache(new \Yiisoft\Test\Support\SimpleCache\MemorySimpleCache())
            ),
            PHP);
        $this->replaceParams("'newMigrationPath' => '',", "'newMigrationPath' => __DIR__,");
        $this->replaceParams(
            "'newMigrationTransactional' => null,",
            "'newMigrationTransactional' => " . var_export($default, true) . ',',
        );

        [$output, $exitCode] = $this->runYiiDbMigration(['migrate:create', 'post', "--command=$command", ...$options]);

        $this->assertSame(0, $exitCode, $output);
        $files = glob(dirname(__DIR__, 2) . '/runtime/tests/bin/M*.php');
        $this->assertCount(1, $files);
        $this->assertSame($expected, str_contains(file_get_contents($files[0]), 'TransactionalMigrationInterface'));
    }

    public function testWithoutConnection(): void
    {
        [$output, $exitCode] = $this->runYiiDbMigration();

        $this->assertSame(255, $exitCode);
        $this->assertStringContainsString('LogicException: DB connection is not configured.', $output);
    }

    public function testWithoutConfig(): void
    {
        unlink(dirname(__DIR__, 2) . '/runtime/tests/bin/yii-db-migration.php');

        [$output, $exitCode] = $this->runYiiDbMigration();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('not found', $output);
        $this->assertStringContainsString(
            'cp "' . dirname(__DIR__, 2) . '/runtime/tests/bin/vendor/yiisoft/db-migration/bin/yii-db-migration.php" "'
            . dirname(__DIR__, 2) . '/runtime/tests/bin/yii-db-migration.php"',
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
                'maps' => new \Yiisoft\Db\Migration\DatabaseConfig(
                    new \Yiisoft\Db\Sqlite\Connection(
                        new \Yiisoft\Db\Sqlite\Driver('sqlite:' . __DIR__ . '/maps.sqlite'),
                        new \Yiisoft\Db\Cache\SchemaCache(new \Yiisoft\Test\Support\SimpleCache\MemorySimpleCache())
                    ),
                    newMigrationPath: __DIR__ . '/maps',
                    historyTable: 'maps_history',
                ),
            ],
            PHP);
        $directory = dirname(__DIR__, 2) . '/runtime/tests/bin';
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

    public function testRejectsDefaultDatabase(): void
    {
        $this->replaceParams('return [', <<<'PHP'
            $db = new \Yiisoft\Db\Sqlite\Connection(
                new \Yiisoft\Db\Sqlite\Driver('sqlite::memory:'),
                new \Yiisoft\Db\Cache\SchemaCache(new \Yiisoft\Test\Support\SimpleCache\MemorySimpleCache())
            );
            return [
            PHP);
        $this->replaceParams("'db' => null,", "'db' => \$db,");
        $this->replaceParams(
            "'databases' => [],",
            "'databases' => ['default' => new \\Yiisoft\\Db\\Migration\\DatabaseConfig(\$db)],",
        );

        [$output, $exitCode] = $this->runYiiDbMigration();

        $this->assertSame(255, $exitCode);
        $this->assertStringContainsString(
            'The "default" database is configured by the existing migration settings and can\'t be redefined.',
            $output,
        );
    }

    private function replaceParams($search, $replace): void
    {
        $file = dirname(__DIR__, 2) . '/runtime/tests/bin/yii-db-migration.php';
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
