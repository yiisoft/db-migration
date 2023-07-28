<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Common;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Yii\Db\Migration\Informer\NullMigrationInformer;
use Yiisoft\Yii\Db\Migration\Migrator;
use Yiisoft\Yii\Db\Migration\Tests\Support\Stub\StubMigration;

abstract class AbstractMigratorTest extends TestCase
{
    protected ContainerInterface $container;

    public function testGetMigrationNameLimitPredefined(): void
    {
        $migrator = new Migrator(
            $this->container->get(ConnectionInterface::class),
            $this->container->get(SchemaCache::class),
            new NullMigrationInformer(),
            null,
            '{{%migration}}',
            42
        );

        $this->assertSame(42, $migrator->getMigrationNameLimit());
    }

    public function testGetMigrationNameLimitWithoutHistoryTable(): void
    {
        $migrator = new Migrator(
            $this->container->get(ConnectionInterface::class),
            $this->container->get(SchemaCache::class),
            new NullMigrationInformer(),
            null,
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
            $this->container->get(SchemaCache::class),
            new NullMigrationInformer(),
            null,
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
            $this->container->get(SchemaCache::class),
            new NullMigrationInformer(),
            null,
            '{{%migration}}',
            null
        );

        // For create migration history table
        $migrator->up(new StubMigration());

        $this->assertGreaterThan(180, $migrator->getMigrationNameLimit());
    }
}
