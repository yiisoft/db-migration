<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Yii\Db\Migration\Informer\NullMigrationInformer;
use Yiisoft\Yii\Db\Migration\Migrator;
use Yiisoft\Yii\Db\Migration\Tests\Support\Helper\PostgreSqlHelper;
use Yiisoft\Yii\Db\Migration\Tests\Support\Helper\SqLiteHelper;
use Yiisoft\Yii\Db\Migration\Tests\Support\Stub\StubMigration;

final class MigratorTest extends TestCase
{
    public function testGetMigrationNameLimitPredefined(): void
    {
        $container = SqLiteHelper::createContainer();
        SqLiteHelper::clearDatabase($container);

        $migrator = new Migrator(
            $container->get(ConnectionInterface::class),
            $container->get(SchemaCache::class),
            new NullMigrationInformer(),
            '{{%migration}}',
            42
        );

        $this->assertSame(42, $migrator->getMigrationNameLimit());
    }

    public function testGetMigrationNameLimitWithoutHistoryTable(): void
    {
        $container = SqLiteHelper::createContainer();
        SqLiteHelper::clearDatabase($container);

        $migrator = new Migrator(
            $container->get(ConnectionInterface::class),
            $container->get(SchemaCache::class),
            new NullMigrationInformer(),
            '{{%migration}}',
            null
        );

        $this->assertNull($migrator->getMigrationNameLimit());
    }

    public function testGetMigrationNameLimitWithoutColumnSize(): void
    {
        $container = PostgreSqlHelper::createContainer();
        PostgreSqlHelper::clearDatabase($container);

        $db = $container->get(ConnectionInterface::class);

        $migrator = new Migrator(
            $db,
            $container->get(SchemaCache::class),
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
        $container = SqLiteHelper::createContainer();
        SqLiteHelper::clearDatabase($container);

        $migrator = new Migrator(
            $container->get(ConnectionInterface::class),
            $container->get(SchemaCache::class),
            new NullMigrationInformer(),
            '{{%migration}}',
            null
        );

        // For create migration history table
        $migrator->up(new StubMigration());

        $this->assertGreaterThan(180, $migrator->getMigrationNameLimit());
    }
}
