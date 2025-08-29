<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Driver\Oracle;

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Migration\Tests\Common\AbstractMigrationBuilderTest;
use Yiisoft\Db\Migration\Tests\Support\Factory\OracleFactory;
use Yiisoft\Db\Oracle\Column\ColumnBuilder;

/**
 * @group oracle
 */
final class MigrationBuilderTest extends AbstractMigrationBuilderTest
{
    public function setUp(): void
    {
        $this->container = OracleFactory::createContainer();

        parent::setUp();
    }

    public function testCreateTableAnotherSchema(): void
    {
        $db = $this->container->get(ConnectionInterface::class);
        $command = $db->createCommand();

        $command->setSql('CREATE USER yii IDENTIFIED BY yiiSCHEMA')->execute();

        $this->builder->createTable('YII.test', ['id' => ColumnBuilder::primaryKey()]);
        $tableSchema = $db->getSchema()->getTableSchema('YII.test');
        $column = $tableSchema->getColumn('id');

        $this->assertNotEmpty($tableSchema);
        $this->assertSame('id', $column->getName());
        $this->assertSame('integer', $column->getType());
        $this->assertTrue($column->isPrimaryKey());
        $this->assertTrue($column->isAutoIncrement());
        $this->assertInformerOutputContains('    > create table YII.test ... Done');

        $this->builder->dropTable('YII.test');

        $command->setSql('DROP USER yii CASCADE')->execute();
    }

    public function testColumnBuilder(): void
    {
        $this->assertSame(ColumnBuilder::class, $this->builder->columnBuilder());
    }
}
