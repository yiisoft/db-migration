<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Common;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Yii\Db\Migration\Informer\NullMigrationInformer;
use Yiisoft\Yii\Db\Migration\Migrator;
use Yiisoft\Yii\Db\Migration\Tests\Support\Stub\StubMigration;
use Yiisoft\Yii\Db\Migration\Tests\Support\Stub\StubRevertibleMigration;

abstract class AbstractMigratorTest extends TestCase
{
    protected ContainerInterface $container;
    protected ConnectionInterface $db;

    public function testUpDown(): void
    {
        $migrator = new Migrator(
            $this->db,
            new NullMigrationInformer(),
            '{{%migration}}',
            null
        );
        $stubRevertibleMigration = new StubRevertibleMigration();

        $migrator->up($stubRevertibleMigration);
        $migrator->down($stubRevertibleMigration);

        $this->assertNotNull($this->db->getTableSchema('{{%migration}}'));
    }

    public function testGetMigrationNameLimitPredefined(): void
    {
        $migrator = new Migrator(
            $this->db,
            new NullMigrationInformer(),
            '{{%migration}}',
            42
        );

        $this->assertSame(42, $migrator->getMigrationNameLimit());
    }

    public function testGetMigrationNameLimitWithoutHistoryTable(): void
    {
        $migrator = new Migrator(
            $this->db,
            new NullMigrationInformer(),
            '{{%migration}}',
            null
        );

        $this->assertNull($migrator->getMigrationNameLimit());
    }

    public function testGetMigrationNameLimitWithoutColumnSize(): void
    {
        if ($this->db->getDriverName() === 'oci') {
            $this->markTestSkipped('Should be fixed for Oracle.');
        }

        $migrator = new Migrator(
            $this->db,
            new NullMigrationInformer(),
            '{{%migration}}',
            null
        );

        // For create migration history table
        $migrator->up(new StubMigration());

        // Change column "name"
        $this->db->createCommand()->alterColumn(
            $migrator->getHistoryTable(),
            'name',
            'text'
        )->execute();

        $this->assertNull($migrator->getMigrationNameLimit());
    }

    public function testGetMigrationNameLimitFromSchema(): void
    {
        $migrator = new Migrator(
            $this->db,
            new NullMigrationInformer(),
            '{{%migration}}',
            null
        );

        // For create migration history table
        $migrator->up(new StubMigration());

        $this->assertGreaterThan(180, $migrator->getMigrationNameLimit());
    }
}
