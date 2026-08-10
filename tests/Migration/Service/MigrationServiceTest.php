<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Migration\Service;

use LogicException;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Migration\Informer\NullMigrationInformer;
use Yiisoft\Db\Migration\Migrator;
use Yiisoft\Db\Migration\Service\MigrationService;
use Yiisoft\Db\Sqlite\Connection;
use Yiisoft\Db\Sqlite\Driver;
use Yiisoft\Injector\Injector;
use Yiisoft\Test\Support\SimpleCache\MemorySimpleCache;

use function dirname;

final class MigrationServiceTest extends TestCase
{
    public function testInvalidNamespace(): void
    {
        $db = new Connection(
            new Driver('sqlite::memory:'),
            new SchemaCache(new MemorySimpleCache()),
        );
        $service = new MigrationService(
            $db,
            new Injector(),
            new Migrator($db, new NullMigrationInformer()),
        );
        $service->setNewMigrationNamespace('InvalidNamespace\\Hello');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Invalid namespace "InvalidNamespace\Hello"');

        $service->findMigrationPath();
    }

    /**
     * A `sourcePaths` directory not covered by any PSR-4 entry must be discovered.
     */
    public function testGetNewMigrationsWithSourcePathNotCoveredByPsr4Map(): void
    {
        $path = dirname(__DIR__, 3) . '/tests-fixtures/NonPsr4Migrations';

        $db = new Connection(
            new Driver('sqlite::memory:'),
            new SchemaCache(new MemorySimpleCache()),
        );
        $service = new MigrationService(
            $db,
            new Injector(),
            new Migrator($db, new NullMigrationInformer()),
        );
        $service->setSourcePaths([$path]);

        $this->assertSame(
            ['M231108183919NotCoveredByPsr4'],
            $service->getNewMigrations(),
        );
    }
}
