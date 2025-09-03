<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Driver\Mysql;

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Constraint\Index;
use Yiisoft\Db\Migration\Tests\Common\AbstractMigrationBuilderTest;
use Yiisoft\Db\Migration\Tests\Support\Factory\MysqlFactory;
use Yiisoft\Db\Mysql\Column\ColumnBuilder;
use Yiisoft\Db\Mysql\IndexType;
use Yiisoft\Db\Mysql\IndexMethod;

/**
 * @group mysql
 */
final class MigrationBuilderTest extends AbstractMigrationBuilderTest
{
    public function setUp(): void
    {
        $this->container = MysqlFactory::createContainer();

        parent::setUp();
    }

    public function testCreateTableAnotherSchema(): void
    {
        $db = $this->container->get(ConnectionInterface::class);
        $command = $db->createCommand();

        $command->setSql('CREATE SCHEMA yii')->execute();

        $this->builder->createTable('yii.test', ['id' => ColumnBuilder::primaryKey()]);
        $tableSchema = $db->getSchema()->getTableSchema('yii.test');
        $column = $tableSchema->getColumn('id');

        $this->assertNotEmpty($tableSchema);
        $this->assertSame('id', $column->getName());
        $this->assertSame('integer', $column->getType());
        $this->assertTrue($column->isPrimaryKey());
        $this->assertTrue($column->isAutoIncrement());
        $this->assertInformerOutputContains('    > create table yii.test ... Done');

        $this->builder->dropTable('yii.test');
        $command->setSql('DROP SCHEMA yii')->execute();
    }

    public function testCreateIndexWithMethod(): void
    {
        $this->builder->createTable('test_table', ['id' => 'int']);
        $this->builder->createIndex('test_table', 'unique_index', 'id', IndexType::UNIQUE, IndexMethod::BTREE);

        $this->assertEquals(
            ['unique_index' => new Index('unique_index', ['id'], true)],
            $this->db->getSchema()->getTableIndexes('test_table'),
        );

        $this->assertInformerOutputContains(
            '    > Create UNIQUE index unique_index on test_table (id) using BTREE ... Done in ',
        );

        $this->builder->dropTable('test_table');
    }

    public function testColumnBuilder(): void
    {
        $this->assertSame(ColumnBuilder::class, $this->builder->columnBuilder());
    }
}
