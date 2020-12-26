<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests;

use Yiisoft\Db\Exception\IntegrityException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Yii\Db\Migration\MigrationHelper;

final class MigrationHelperTest extends BaseTest
{
    public function setUp(): void
    {
        parent::setUp();

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
        $m = $this->getHelper();

        ob_start();
        $m->execute('DROP TABLE test_table');
        $output = ob_get_clean();

        $this->assertEmpty($this->getDb()->getSchema()->getTableSchema('test_table'));
        $this->assertStringContainsString('    > Execute SQL: DROP TABLE test_table ... Done in ', $output);
    }

    public function testInsert(): void
    {
        $m = $this->getHelper();

        ob_start();
        $m->insert('test_table', ['id' => 1]);
        $output = ob_get_clean();

        $this->assertEquals(
            '1',
            $this->getDb()->createCommand('SELECT count(*) FROM test_table WHERE id = 1')->queryScalar()
        );
        $this->assertStringContainsString('    > Insert into test_table ... Done in ', $output);
    }

    public function testBatchInsert(): void
    {
        $m = $this->getHelper();

        ob_start();
        $m->batchInsert('test_table', ['id'], [['id' => 1], ['id' => 2]]);
        $output = ob_get_clean();

        $this->assertEquals(
            '2',
            $this->getDb()->createCommand('SELECT count(*) FROM test_table WHERE id IN (1, 2)')->queryScalar()
        );
        $this->assertStringContainsString('    > Insert into test_table ... Done in ', $output);
    }

    public function testUpsert(): void
    {
        $m = $this->getHelper();

        ob_start();
        $m->insert('test_table', ['id' => 1]);
        $m->upsert('test_table', ['id' => 1], false);
        $output = ob_get_clean();

        $this->assertEquals(
            [
                ['id' => 1],
            ],
            $this->getDb()->createCommand('SELECT id FROM test_table')->queryAll()
        );
        $this->assertStringContainsString('    > Upsert into test_table ... Done in ', $output);
    }

    public function testUpdate(): void
    {
        $m = $this->getHelper();

        ob_start();
        $m->insert('test_table', ['id' => 1]);
        $m->update('test_table', ['id' => 2], 'id=:id', ['id' => 1]);
        $output = ob_get_clean();

        $this->assertEquals(
            [
                ['id' => 2],
            ],
            $this->getDb()->createCommand('SELECT id FROM test_table')->queryAll()
        );
        $this->assertStringContainsString('    > Update test_table ... Done in ', $output);
    }

    public function testDelete(): void
    {
        $m = $this->getHelper();

        ob_start();
        $m->insert('test_table', ['id' => 1]);
        $m->delete('test_table', 'id=:id', ['id' => 1]);
        $output = ob_get_clean();

        $this->assertEquals('0', $this->getDb()->createCommand('SELECT count(*) FROM test_table')->queryScalar());
        $this->assertStringContainsString('    > Delete from test_table ... Done in ', $output);
    }

    public function testCreateTable(): void
    {
        $m = $this->getHelper();

        ob_start();
        $m->createTable('test_create_table', ['id' => $m->primaryKey()]);
        $output = ob_get_clean();

        $this->assertNotEmpty($this->getDb()->getSchema()->getTableSchema('test_create_table'));
        $this->assertStringContainsString('    > create table test_create_table ... Done in ', $output);
    }

    public function testDropTable(): void
    {
        $m = $this->getHelper();

        ob_start();
        $m->dropTable('test_table');
        $output = ob_get_clean();

        $this->assertEmpty($this->getDb()->getSchema()->getTableSchema('test_table'));
        $this->assertStringContainsString('    > Drop table test_table ... Done in ', $output);
    }

    public function testRenameColumn(): void
    {
        $m = $this->getHelper(true);

        $this->expectException(NotSupportedException::class);
        $m->renameColumn('test_table', 'id', 'id_new');
    }

    public function testAlterColumn(): void
    {
        $m = $this->getHelper(true);

        $this->expectException(NotSupportedException::class);
        $m->alterColumn('test_table', 'id', $m->string());
    }

    public function testAddPrimaryKey(): void
    {
        $m = $this->getHelper();

        ob_start();
        $m->createTable('test_create_table', ['id2' => $m->integer()]);
        $m->addPrimaryKey('id2', 'test_create_table', ['id2']);
        $output = ob_get_clean();

        $this->assertTrue(
            $this->getDb()->getSchema()->getTableSchema('test_create_table')->getColumn('id2')->isPrimaryKey()
        );
        $this->assertStringContainsString(
            '    > Add primary key id2 on test_create_table (id2) ... Done in ',
            $output
        );
    }

    public function testDropPrimaryKey(): void
    {
        $m = $this->getHelper(true);

        $m->createTable('test_create_table', ['id' => $m->primaryKey()]);
        $this->expectException(NotSupportedException::class);
        $m->dropPrimaryKey('id', 'test_create_table');
    }

    public function testAddForeignKey(): void
    {
        $m = $this->getHelper();

        ob_start();
        $m->createTable('target_table', ['id' => $m->primaryKey()]);
        $m->addForeignKey(
            'fk',
            'test_table',
            'foreign_id',
            'target_table',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $output = ob_get_clean();

        $this->assertStringContainsString(
            '    > Add foreign key fk: test_table (foreign_id) references target_table (id) ... Done in',
            $output
        );
    }

    public function testDropForeignKey(): void
    {
        $m = $this->getHelper();

        ob_start();
        $m->createTable('target_table', ['id2' => $m->primaryKey()]);
        $m->addForeignKey(
            'fk2',
            'test_table',
            'foreign_id',
            'target_table',
            'id2',
            'CASCADE',
            'CASCADE'
        );
        $m->dropForeignKey('fk2', 'test_table');
        $output = ob_get_clean();

        $this->assertStringContainsString(
            '    > Drop foreign key fk2 from table test_table ... Done',
            $output
        );
    }

    public function testCreateIndex(): void
    {
        $m = $this->getHelper();

        ob_start();
        $m->createIndex('unique_index', 'test_table', 'foreign_id', true);
        $output = ob_get_clean();

        $this->assertStringContainsString(
            '    > Create unique index unique_index on test_table (foreign_id) ... Done in ',
            $output
        );

        $this->getDb()->createCommand()->insert('test_table', ['id' => 1, 'foreign_id' => 1])->execute();
        $this->expectException(IntegrityException::class);
        $this->getDb()->createCommand()->insert('test_table', ['id' => 2, 'foreign_id' => 1])->execute();
    }

    public function testDropIndex(): void
    {
        $m = $this->getHelper();

        ob_start();
        $m->createIndex('unique_index', 'test_table', 'foreign_id', true);
        $m->dropIndex('unique_index', 'test_table');
        $output = ob_get_clean();

        $this->assertStringContainsString('    > Drop index unique_index on test_table ... Done in ', $output);
    }

    public function testAddCommentOnColumn(): void
    {
        $m = $this->getHelper(true);

        $this->expectException(NotSupportedException::class);
        $m->addCommentOnColumn('test_table', 'id', 'test comment');
    }

    public function testAddCommentOnTable(): void
    {
        $m = $this->getHelper(true);

        $this->expectException(NotSupportedException::class);
        $m->addCommentOnTable('test_table', 'id');
    }

    public function testDropCommentFromColumn(): void
    {
        $m = $this->getHelper(true);

        $this->expectException(NotSupportedException::class);
        $m->dropCommentFromColumn('test_table', 'id');
    }

    public function testDropCommentFromTable(): void
    {
        $m = $this->getHelper(true);

        $this->expectException(NotSupportedException::class);
        $m->dropCommentFromTable('test_table');
    }

    public function testMaxSqlOutputLength(): void
    {
        $m = $this->getHelper(false, 15);

        ob_start();
        $m->execute('SELECT (1+2+3+4+5+6+7+8+9+10+11)');
        $output = ob_get_clean();

        $this->assertMatchesRegularExpression('/.*SEL\[\.\.\. hidden\].*/', $output);
    }

    private function getHelper(bool $compact = false, int $maxSqlOutputLength = 0): MigrationHelper
    {
        return new MigrationHelper($this->getDb(), $compact, $maxSqlOutputLength);
    }
}
