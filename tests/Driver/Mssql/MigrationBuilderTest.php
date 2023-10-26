<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Driver\Mssql;

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Migration\Tests\Common\AbstractMigrationBuilderTest;
use Yiisoft\Db\Migration\Tests\Support\Factory\MssqlFactory;

/**
 * @group mssql
 */
final class MigrationBuilderTest extends AbstractMigrationBuilderTest
{
    public function setUp(): void
    {
        $this->container = MssqlFactory::createContainer();

        parent::setUp();
    }

    public function testCreateTableAnotherSchema(): void
    {
        $db = $this->container->get(ConnectionInterface::class);
        $command = $db->createCommand();

        $command->setSql('CREATE SCHEMA yii')->execute();

        $this->builder->createTable('yii.test', ['id' => $this->builder->primaryKey()]);
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

    /** @link https://github.com/yiisoft/db-migration/issues/11 */
    public function testAlterColumnWithConstraint()
    {
        $b = $this->builder;

        $b->createTable('check_alter_column', ['id' => $b->primaryKey()]);

        $b->addColumn('check_alter_column', 'field', $b->integer()->null());
        $b->alterColumn('check_alter_column', 'field', $b->string(40)->notNull());

        $tableSchema = $this->db->getTableSchema('check_alter_column', true);

        $field = $tableSchema->getColumn('field');

        $this->assertFalse($field->isAllowNull());
        $this->assertNull($field->getDefaultValue());
        $this->assertSame('nvarchar(40)', $field->getDbType());

        $b->dropTable('check_alter_column');
    }
}
