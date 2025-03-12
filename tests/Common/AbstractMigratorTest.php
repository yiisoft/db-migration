<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Common;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\IntegrityException;
use Yiisoft\Db\Migration\Informer\NullMigrationInformer;
use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\Migrator;
use Yiisoft\Db\Migration\Tests\Support\Migrations\M231015155500ExecuteSql;
use Yiisoft\Db\Migration\Tests\Support\Migrations\M250312122500ChangeDbPrefixDown;
use Yiisoft\Db\Migration\Tests\Support\Migrations\M250312122400ChangeDbPrefixUp;
use Yiisoft\Db\Migration\Tests\Support\Stub\StubMigration;
use Yiisoft\Db\Migration\Tests\Support\Stub\StubMigrationInformer;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Schema\Column\ColumnBuilder;

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

        $limit = $migrator->getMigrationNameLimit();

        match ($db->getDriverName()) {
            'sqlsrv' => $this->assertSame(2147483647, $limit),
            default => $this->assertNull($limit),
        };
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

    public function testChangeDbPrefixUp(): void
    {
        $db = $this->container->get(ConnectionInterface::class);
        $informer = new StubMigrationInformer();
        $builder = new MigrationBuilder($db, $informer);

        if ($db->getSchema()->getTableSchema('prefix_user', true) === null) {
            $builder->createTable('prefix_user', [
                'id' => ColumnBuilder::primaryKey(),
                'name' => ColumnBuilder::string(),
            ]);
        } else {
            $builder->truncateTable('prefix_user');
        }

        $migrator = new Migrator($db, $informer);

        try {
            $migrator->up(new M250312122400ChangeDbPrefixUp());
        } catch (\Exception $e) {
            $this->assertExceptionTableNotExist($e);
        }

        $users = (new Query($db))->from('prefix_user')->all();
        $this->assertCount(0, $users);
    }

    public function testChangeDbPrefixDown(): void
    {
        $db = $this->container->get(ConnectionInterface::class);
        $informer = new StubMigrationInformer();
        $builder = new MigrationBuilder($db, $informer);

        if ($db->getSchema()->getTableSchema('prefix_user', true) === null) {
            $builder->createTable('prefix_user', [
                'id' => ColumnBuilder::primaryKey(),
                'name' => ColumnBuilder::string(),
            ]);
        } else {
            $builder->truncateTable('prefix_user');
        }

        $migrator = new Migrator($db, $informer);
        $migrator->up(new M250312122500ChangeDbPrefixDown());

        $users = (new Query($db))->from('prefix_user')->all();
        $this->assertCount(1, $users);

        try {
            $migrator->down(new M250312122500ChangeDbPrefixDown());
        } catch (\Exception $e) {
            $this->assertExceptionTableNotExist($e);
        }

        $users = (new Query($db))->from('prefix_user')->all();
        $this->assertCount(1, $users);
    }

    private function assertExceptionTableNotExist(\Exception $e): void
    {
        $db = $this->container->get(ConnectionInterface::class);

        $exceptionClass = match ($db->getDriverName()) {
            'oci' => IntegrityException::class,
            default => Exception::class,
        };

        $this->assertInstanceOf($exceptionClass, $e);

        $exceptionMessage = match ($db->getDriverName()) {
            'oci' => 'SQLSTATE[HY000]: General error: 942 OCIStmtExecute: ORA-00942: table or view does not exist',
            'sqlite' => 'SQLSTATE[HY000]: General error: 1 no such table: prefix_migration',
            'pgsql' => 'SQLSTATE[42P01]: Undefined table: 7 ERROR:  relation "prefix_migration" does not exist',
            'mysql' => "SQLSTATE[42S02]: Base table or view not found: 1146 Table 'yiitest.prefix_migration' doesn't exist",
            'sqlsrv' => "SQLSTATE[42S02]: [Microsoft][ODBC Driver 17 for SQL Server][SQL Server]Invalid object name 'prefix_migration'.",
        };

        $this->assertStringContainsString($exceptionMessage, $e->getMessage());
    }
}
