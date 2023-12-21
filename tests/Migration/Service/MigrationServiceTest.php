<?php

declare(strict_types=1);

namespace Service;

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

final class MigrationServiceTest extends TestCase
{
    public function testInvalidNamespace(): void
    {
        $db = new Connection(
            new Driver('sqlite::memory:'),
            new SchemaCache(new MemorySimpleCache())
        );
        $service = new MigrationService(
            $db,
            new Injector(),
            new Migrator($db, new NullMigrationInformer()),
        );
        $service->setNewMigrationNamespace('InvalidNamespace\\Hello');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Invalid namespace: "InvalidNamespace\Hello".');
        $service->findMigrationPath();
    }
}
