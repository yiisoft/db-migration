<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Driver\Mysql;

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Migration\Tests\Common\AbstractMigrationBuilderTest;
use Yiisoft\Db\Migration\Tests\Support\Factory\MysqlFactory;

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
}
