<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Migration;

use PHPUnit\Framework\TestCase;
use Yiisoft\Files\FileHelper;

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
        $this->replaceParams(
            "'db' => null,",
            <<<'PHP'
            'db' => new \Yiisoft\Db\Sqlite\Connection(
                new \Yiisoft\Db\Sqlite\Driver('sqlite::memory:'),
                new \Yiisoft\Db\Cache\SchemaCache(new \Yiisoft\Test\Support\SimpleCache\MemorySimpleCache())
            ),
            PHP
        );

        [$output, $exitCode] = $this->runYiiDbMigration();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Yii Database Migration Tool', $output);
    }

    public function testWithoutConnection(): void
    {
        [$output, $exitCode] = $this->runYiiDbMigration();

        $this->assertSame(255, $exitCode);
        $this->assertStringContainsString('LogicException: DB connection is not configured.', $output);
    }

    private function replaceParams($search, $replace): void
    {
        $file = dirname(__DIR__) . '/runtime/bin/yii-db-migration.php';
        file_put_contents(
            $file,
            str_replace($search, $replace, file_get_contents($file))
        );
    }

    private function runYiiDbMigration(): array
    {
        exec(__DIR__ . '/bin-runner.php', $output, $exitCode);
        return [implode("\n", $output), $exitCode];
    }
}
