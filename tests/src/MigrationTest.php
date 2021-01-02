<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests;

use Yiisoft\Db\Exception\IntegrityException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Yii\Db\Migration\Migration;

final class MigrationTest extends BaseTest
{
    private Migration $migration;

    public function setUp(): void
    {
        parent::setUp();

        $this->migration = $this->getContainer()->get(MigrationStub::class);

        $this->getDb()->createCommand()->createTable(
            'test_table',
            [
                'id' => 'INTEGER NOT NULL PRIMARY KEY',
                'foreign_id' => 'INTEGER',
            ]
        )->execute();
    }

    public function testExecute(): void
    {
        ob_start();

        $this->migration->execute('DROP TABLE test_table');

        $this->assertEmpty($this->getDb()->getSchema()->getTableSchema('test_table'));
        $this->assertStringContainsString('    > Execute SQL: DROP TABLE test_table ... Done in ', ob_get_clean());
    }

    public function testInsert(): void
    {
        ob_start();

        $this->migration->insert('test_table', ['id' => 1]);

        $this->assertEquals(
            '1',
            $this->getDb()->createCommand('SELECT count(*) FROM test_table WHERE id = 1')->queryScalar()
        );
        $this->assertStringContainsString('    > Insert into test_table ... Done in ', ob_get_clean());
    }

    public function testBatchInsert(): void
    {
        ob_start();

        $this->migration->batchInsert('test_table', ['id'], [['id' => 1], ['id' => 2]]);

        $this->assertEquals(
            '2',
            $this->getDb()->createCommand('SELECT count(*) FROM test_table WHERE id IN (1, 2)')->queryScalar()
        );
        $this->assertStringContainsString('    > Insert into test_table ... Done in ', ob_get_clean());
    }

    public function testUpsert(): void
    {
        ob_start();

        $this->migration->insert('test_table', ['id' => 1]);

        $this->migration->upsert('test_table', ['id' => 1], false);

        $this->assertEquals(
            [
                ['id' => 1],
            ],
            $this->getDb()->createCommand('SELECT id FROM test_table')->queryAll()
        );
        $this->assertStringContainsString('    > Upsert into test_table ... Done in ', ob_get_clean());
    }

    public function testUpdate(): void
    {
        ob_start();

        $this->migration->insert('test_table', ['id' => 1]);

        $this->migration->update('test_table', ['id' => 2], 'id=:id', ['id' => 1]);

        $this->assertEquals(
            [
                ['id' => 2],
            ],
            $this->getDb()->createCommand('SELECT id FROM test_table')->queryAll()
        );
        $this->assertStringContainsString('    > Update test_table ... Done in ', ob_get_clean());
    }

    public function testDelete(): void
    {
        ob_start();

        $this->migration->insert('test_table', ['id' => 1]);

        $this->migration->delete('test_table', 'id=:id', ['id' => 1]);

        $this->assertEquals('0', $this->getDb()->createCommand('SELECT count(*) FROM test_table')->queryScalar());
        $this->assertStringContainsString('    > Delete from test_table ... Done in ', ob_get_clean());
    }

    public function testCreateTable(): void
    {
        ob_start();

        $this->migration->createTable('test_create_table', ['id' => $this->migration->primaryKey()]);

        $this->assertNotEmpty($this->getDb()->getSchema()->getTableSchema('test_create_table'));
        $this->assertStringContainsString('    > create table test_create_table ... Done in ', ob_get_clean());
    }

    public function testCreateTableWithStringColumnDefinition(): void
    {
        ob_start();

        $this->migration->createTable('test_create_table', ['name' => 'varchar(50)']);

        $this->assertNotEmpty($this->getDb()->getSchema()->getTableSchema('test_create_table'));
        $this->assertStringContainsString('    > create table test_create_table ... Done in ', ob_get_clean());
    }

    public function testDropTable(): void
    {
        ob_start();

        $this->migration->dropTable('test_table');

        $this->assertEmpty($this->getDb()->getSchema()->getTableSchema('test_table'));
        $this->assertStringContainsString('    > Drop table test_table ... Done in ', ob_get_clean());
    }

    public function testRenameColumn(): void
    {
        $this->expectException(NotSupportedException::class);

        $this->migration->compact(true);

        $this->migration->renameColumn('test_table', 'id', 'id_new');
    }

    public function testAlterColumn(): void
    {
        $this->expectException(NotSupportedException::class);

        $this->migration->compact(true);

        $this->migration->alterColumn('test_table', 'id', $this->migration->string());
    }

    public function testAddPrimaryKey(): void
    {
        ob_start();

        $this->migration->createTable('test_create_table', ['id2' => $this->migration->integer()]);

        $this->migration->addPrimaryKey('id2', 'test_create_table', ['id2']);

        $this->assertTrue(
            $this->getDb()->getSchema()->getTableSchema('test_create_table')->getColumn('id2')->isPrimaryKey()
        );
        $this->assertStringContainsString(
            '    > Add primary key id2 on test_create_table (id2) ... Done in ',
            ob_get_clean()
        );
    }

    public function testDropPrimaryKey(): void
    {
        $this->expectException(NotSupportedException::class);

        $this->migration->compact(true);

        $this->migration->createTable('test_create_table', ['id' => $this->migration->primaryKey()]);

        $this->migration->dropPrimaryKey('id', 'test_create_table');
    }

    public function testAddForeignKey(): void
    {
        ob_start();

        $this->migration->createTable('target_table', ['id' => $this->migration->primaryKey()]);

        $this->migration->addForeignKey(
            'fk',
            'test_table',
            'foreign_id',
            'target_table',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->assertStringContainsString(
            '    > Add foreign key fk: test_table (foreign_id) references target_table (id) ... Done in',
            ob_get_clean()
        );
    }

    public function testDropForeignKey(): void
    {
        ob_start();

        $this->migration->createTable('target_table', ['id2' => $this->migration->primaryKey()]);

        $this->migration->addForeignKey(
            'fk2',
            'test_table',
            'foreign_id',
            'target_table',
            'id2',
            'CASCADE',
            'CASCADE'
        );

        $this->migration->dropForeignKey('fk2', 'test_table');

        $this->assertStringContainsString(
            '    > Drop foreign key fk2 from table test_table ... Done',
            ob_get_clean()
        );
    }

    public function testCreateIndex(): void
    {
        ob_start();

        $this->migration->createIndex('unique_index', 'test_table', 'foreign_id', true);

        $this->assertStringContainsString(
            '    > Create unique index unique_index on test_table (foreign_id) ... Done in ',
            ob_get_clean()
        );

        $this->getDb()->createCommand()->insert('test_table', ['id' => 1, 'foreign_id' => 1])->execute();

        $this->expectException(IntegrityException::class);

        $this->getDb()->createCommand()->insert('test_table', ['id' => 2, 'foreign_id' => 1])->execute();
    }

    public function testDropIndex(): void
    {
        ob_start();

        $this->migration->createIndex('unique_index', 'test_table', 'foreign_id', true);

        $this->migration->dropIndex('unique_index', 'test_table');

        $this->assertStringContainsString('    > Drop index unique_index on test_table ... Done in ', ob_get_clean());

        $this->getDb()->createCommand()->insert('test_table', ['id' => 1, 'foreign_id' => 1])->execute();
        $this->getDb()->createCommand()->insert('test_table', ['id' => 2, 'foreign_id' => 1])->execute();
    }

    public function testAddCommentOnColumn(): void
    {
        $this->expectException(NotSupportedException::class);

        $this->migration->compact(true);

        $this->migration->addCommentOnColumn('test_table', 'id', 'test comment');
    }

    public function testAddCommentOnTable(): void
    {
        $this->expectException(NotSupportedException::class);

        $this->migration->compact(true);

        $this->migration->addCommentOnTable('test_table', 'id');
    }

    public function testDropCommentFromColumn(): void
    {
        $this->expectException(NotSupportedException::class);

        $this->migration->compact(true);

        $this->migration->dropCommentFromColumn('test_table', 'id');
    }

    public function testDropCommentFromTable(): void
    {
        $this->expectException(NotSupportedException::class);

        $this->migration->compact(true);

        $this->migration->dropCommentFromTable('test_table');
    }

    public function testMaxSqlOutputLength(): void
    {
        $this->migration->maxSqlOutputLength(15);

        ob_start();
        $this->migration->execute('SELECT (1+2+3+4+5+6+7+8+9+10+11)');
        $output = ob_get_clean();

        $this->assertMatchesRegularExpression('/.*SEL\[\.\.\. hidden\].*/', $output);
    }
}
