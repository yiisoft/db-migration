<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Common;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Migration\Informer\NullMigrationInformer;
use Yiisoft\Db\Migration\Migrator;
use Yiisoft\Db\Migration\Tests\Support\Migrations\M231015155500ExecuteSql;
use Yiisoft\Db\Migration\Tests\Support\Stub\StubMigration;
use Yiisoft\Db\Migration\Tests\Support\Stub\StubMigrationInformer;

abstract class AbstractMigratorTest extends TestCase
{
    protected ContainerInterface $container;

    public function testGetMigrationNameLimitPredefined(): void
    {
        $migrator = new Migrator(
            $this->container->get(ConnectionInterface::class),
            new NullMigrationInformer(),
            '{{%migration}}',
            42
        );

        $this->assertSame(42, $migrator->getMigrationNameLimit());
    }

    public function testGetMigrationNameLimitWithoutHistoryTable(): void
    {
        $migrator = new Migrator(
            $this->container->get(ConnectionInterface::class),
            new NullMigrationInformer(),
            '{{%migration}}',
            null
        );

        $this->assertNull($migrator->getMigrationNameLimit());
    }

    public function testGetMigrationNameLimitWithoutColumnSize(): void
    {
        $db = $this->container->get(ConnectionInterface::class);

        if ($db->getDriverName() === 'oci') {
            $this->markTestSkipped('Should be fixed for Oracle.');
        }

        $migrator = new Migrator(
            $db,
            new NullMigrationInformer(),
            '{{%migration}}',
            null
        );

        // For create migration history table
        $migrator->up(new StubMigration());

        // Change column "name"
        $db->createCommand()->alterColumn(
            $migrator->getHistoryTable(),
            'name',
            'text'
        )->execute();

        $this->assertNull($migrator->getMigrationNameLimit());
    }

    public function testGetMigrationNameLimitFromSchema(): void
    {
        $migrator = new Migrator(
            $this->container->get(ConnectionInterface::class),
            new NullMigrationInformer(),
            '{{%migration}}',
            null
        );

        // For create migration history table
        $migrator->up(new StubMigration());

        $this->assertGreaterThan(180, $migrator->getMigrationNameLimit());
    }

    public function testMaxSqlOutputLength(): void
    {
        $informer = new StubMigrationInformer();

        $migrator = new Migrator(
            $this->container->get(ConnectionInterface::class),
            $informer,
            maxSqlOutputLength: 20,
        );

        $migrator->up(new M231015155500ExecuteSql());

        $this->assertStringContainsString(
            'Execute SQL: CREATE TABLE person [... hidden] ... Done',
            $informer->getOutput(),
        );
    }

    public function testZeroMaxSqlOutputLength(): void
    {
        $informer = new StubMigrationInformer();

        $migrator = new Migrator(
            $this->container->get(ConnectionInterface::class),
            $informer,
            maxSqlOutputLength: 0,
        );

        $migrator->up(new M231015155500ExecuteSql());

        $this->assertStringContainsString(
            'Execute SQL: [... hidden] ... Done',
            $informer->getOutput(),
        );
    }
}
