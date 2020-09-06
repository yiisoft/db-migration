<?php

namespace Yiisoft\Yii\Db\Migration\Tests;

use Yiisoft\Db\Exception\IntegrityException;
use Yiisoft\Db\Exception\NotSupportedException;

final class MigrationTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->db->createCommand()->createTable(
            'test_table',
            [
                'id' =>  'INTEGER NOT NULL PRIMARY KEY',
                'foreign_id' => 'INTEGER'
            ]
        )->execute();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        if ($this->db->getSchema()->getTableSchema('test_table') !== null) {
            $this->db->createCommand()->dropTable('test_table')->execute();
        }

        if ($this->db->getSchema()->getTableSchema('target_table') !== null) {
            $this->db->createCommand()->dropTable('target_table')->execute();
        }

        if ($this->db->getSchema()->getTableSchema('test_create_table') !== null) {
            $this->db->createCommand()->dropTable('test_create_table')->execute();
        }
    }

    public function testExecute(): void
    {
        ob_start();

        $this->migration->execute('DROP TABLE test_table');

        $this->assertEmpty($this->db->getSchema()->getTableSchema('test_table'));
        $this->assertStringContainsString('    > Execute SQL: DROP TABLE test_table ... Done in ', ob_get_clean());
    }

    public function testInsert(): void
    {
        ob_start();

        $this->migration->insert('test_table', ['id' => 1]);

        $this->assertEquals('1', $this->db->createCommand('SELECT count(*) FROM test_table WHERE id = 1')->queryScalar());
        $this->assertStringContainsString('    > Insert into test_table ... Done in ', ob_get_clean());
    }

    public function testBatchInsert(): void
    {
        ob_start();

        $this->migration->batchInsert('test_table', ['id'], [['id' => 1], ['id' => 2]]);

        $this->assertEquals('2', $this->db->createCommand('SELECT count(*) FROM test_table WHERE id IN (1, 2)')->queryScalar());
        $this->assertStringContainsString('    > Insert into test_table ... Done in ', ob_get_clean());
    }

    public function testUpsert(): void
    {
        $this->migration->insert('test_table', ['id' => 1]);

        ob_start();

        $this->migration->upsert('test_table', ['id' => 1], false);

        $this->assertEquals(
            [
                ['id' => 1],
            ],
            $this->db->createCommand('SELECT id FROM test_table')->queryAll()
        );
        $this->assertStringContainsString('    > Upsert into test_table ... Done in ', ob_get_clean());
    }

    public function testUpdate(): void
    {
        $this->migration->insert('test_table', ['id' => 1]);

        ob_start();
        $this->migration->update('test_table', ['id' => 2], 'id=:id', ['id' => 1]);

        $this->assertEquals(
            [
                ['id' => 2],
            ],
            $this->db->createCommand('SELECT id FROM test_table')->queryAll()
        );
        $this->assertStringContainsString('    > Update test_table ... Done in ', ob_get_clean());
    }

    public function testDelete(): void
    {
        $this->migration->insert('test_table', ['id' => 1]);

        ob_start();

        $this->migration->delete('test_table', 'id=:id', ['id' => 1]);

        $this->assertEquals('0', $this->db->createCommand('SELECT count(*) FROM test_table')->queryScalar());
        $this->assertStringContainsString('    > Delete from test_table ... Done in ', ob_get_clean());
    }

    public function testCreateTable(): void
    {
        ob_start();

        $this->migration->createTable('test_create_table', ['id' => $this->migration->primaryKey()]);

        $this->assertNotEmpty($this->db->getSchema()->getTableSchema('test_create_table'));
        $this->assertStringContainsString('    > create table test_create_table ... Done in ', ob_get_clean());
    }

    public function testDropTable(): void
    {
        ob_start();

        $this->migration->dropTable('test_table');

        $this->assertEmpty($this->db->getSchema()->getTableSchema('test_table'));
        $this->assertStringContainsString('    > Drop table test_table ... Done in ', ob_get_clean());
    }

    public function testRenameColumn(): void
    {
        $this->expectException(NotSupportedException::class);

        $this->migration->renameColumn('test_table', 'id', 'id_new');
    }

    public function testAlterColumn(): void
    {
        $this->expectException(NotSupportedException::class);

        $this->migration->alterColumn('test_table', 'id', $this->migration->string());
    }

    public function testAddPrimaryKey(): void
    {
        $this->migration->createTable('test_create_table', ['id2' => $this->migration->integer()]);

        ob_start();

        $this->migration->addPrimaryKey('id2', 'test_create_table', ['id2']);

        $this->assertTrue(
            $this->db->getSchema()->getTableSchema('test_create_table')->getColumn('id2')->isPrimaryKey()
        );
        $this->assertStringContainsString(
            '    > Add primary key id2 on test_create_table (id2) ... Done in ',
            ob_get_clean()
        );
    }

    public function testDropPrimaryKey(): void
    {
        $this->expectException(NotSupportedException::class);

        $this->migration->createTable('test_create_table', ['id' => $this->migration->primaryKey()]);

        $this->migration->dropPrimaryKey('id', 'test_create_table');
    }

    public function testAddForeignKey(): void
    {
        $this->migration->createTable('target_table', ['id' => $this->migration->primaryKey()]);
        ob_start();

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

        ob_start();

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

        $this->db->createCommand()->insert('test_table', ['id' => 1, 'foreign_id' => 1])->execute();

        $this->expectException(IntegrityException::class);

        $this->db->createCommand()->insert('test_table', ['id' => 2, 'foreign_id' => 1])->execute();
    }

    public function testDropIndex(): void
    {
        $this->migration->createIndex('unique_index', 'test_table', 'foreign_id', true);

        ob_start();

        $this->migration->dropIndex('unique_index', 'test_table');

        $this->assertStringContainsString('    > Drop index unique_index on test_table ... Done in ', ob_get_clean());

        $this->db->createCommand()->insert('test_table', ['id' => 1, 'foreign_id' => 1])->execute();
        $this->db->createCommand()->insert('test_table', ['id' => 2, 'foreign_id' => 1])->execute();
    }

    public function testAddCommentOnColumn(): void
    {
        $this->expectException(NotSupportedException::class);

        $this->migration->addCommentOnColumn('test_table', 'id', 'test comment');
    }

    public function testAddCommentOnTable(): void
    {
        $this->expectException(NotSupportedException::class);

        $this->migration->addCommentOnTable('test_table', 'id');
    }

    public function testDropCommentFromColumn(): void
    {
        $this->expectException(NotSupportedException::class);

        $this->migration->dropCommentFromColumn('test_table', 'id');
    }

    public function testDropCommentFromTable(): void
    {
        $this->expectException(NotSupportedException::class);

        $this->migration->dropCommentFromTable('test_table');
    }
}
