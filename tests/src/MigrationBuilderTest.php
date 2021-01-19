<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests;

use Yiisoft\Db\Exception\IntegrityException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Yii\Db\Migration\Informer\InformerInterface;
use Yiisoft\Yii\Db\Migration\MigrationBuilder;

final class MigrationBuilderTest extends BaseTest
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
        $b = $this->getBuilder();

        ob_start();
        $b->execute('DROP TABLE test_table');
        $output = ob_get_clean();

        $this->assertEmpty($this->getDb()->getSchema()->getTableSchema('test_table'));
        $this->assertStringContainsString('    > Execute SQL: DROP TABLE test_table ... Done in ', $output);
    }

    public function testInsert(): void
    {
        $b = $this->getBuilder();

        ob_start();
        $b->insert('test_table', ['id' => 1]);
        $output = ob_get_clean();

        $this->assertEquals(
            '1',
            $this->getDb()->createCommand('SELECT count(*) FROM test_table WHERE id = 1')->queryScalar()
        );
        $this->assertStringContainsString('    > Insert into test_table ... Done in ', $output);
    }

    public function testBatchInsert(): void
    {
        $b = $this->getBuilder();

        ob_start();
        $b->batchInsert('test_table', ['id'], [['id' => 1], ['id' => 2]]);
        $output = ob_get_clean();

        $this->assertEquals(
            '2',
            $this->getDb()->createCommand('SELECT count(*) FROM test_table WHERE id IN (1, 2)')->queryScalar()
        );
        $this->assertStringContainsString('    > Insert into test_table ... Done in ', $output);
    }

    public function testUpsert(): void
    {
        $b = $this->getBuilder();

        ob_start();
        $b->insert('test_table', ['id' => 1]);
        $b->upsert('test_table', ['id' => 1], false);
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
        $b = $this->getBuilder();

        ob_start();
        $b->insert('test_table', ['id' => 1]);
        $b->update('test_table', ['id' => 2], 'id=:id', ['id' => 1]);
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
        $b = $this->getBuilder();

        ob_start();
        $b->insert('test_table', ['id' => 1]);
        $b->delete('test_table', 'id=:id', ['id' => 1]);
        $output = ob_get_clean();

        $this->assertEquals('0', $this->getDb()->createCommand('SELECT count(*) FROM test_table')->queryScalar());
        $this->assertStringContainsString('    > Delete from test_table ... Done in ', $output);
    }

    public function testCreateTable(): void
    {
        $b = $this->getBuilder();

        ob_start();
        $b->createTable('test_create_table', ['id' => $b->primaryKey()]);
        $output = ob_get_clean();

        $this->assertNotEmpty($this->getDb()->getSchema()->getTableSchema('test_create_table'));
        $this->assertStringContainsString('    > create table test_create_table ... Done in ', $output);
    }

    public function testCreateTableWithStringColumnDefinition(): void
    {
        $b = $this->getBuilder();

        ob_start();
        $b->createTable('test_create_table', ['name' => 'varchar(50)']);
        $output = ob_get_clean();

        $this->assertNotEmpty($this->getDb()->getSchema()->getTableSchema('test_create_table'));
        $this->assertStringContainsString('    > create table test_create_table ... Done in ', $output);
    }

    public function testRenameTable(): void
    {
        $b = $this->getBuilder();

        ob_start();
        $b->renameTable('test_table', 'new_table');
        $output = ob_get_clean();

        $this->assertExistsTables('new_table');
        $this->assertStringContainsString('    > rename table test_table to new_table ... Done in ', $output);
    }

    public function testDropTable(): void
    {
        $b = $this->getBuilder();

        ob_start();
        $b->dropTable('test_table');
        $output = ob_get_clean();

        $this->assertEmpty($this->getDb()->getSchema()->getTableSchema('test_table'));
        $this->assertStringContainsString('    > Drop table test_table ... Done in ', $output);
    }

    public function testTruncateTable(): void
    {
        $b = $this->getBuilder();
        $b->insert('test_table', ['foreign_id' => 42]);

        ob_start();
        $b->truncateTable('test_table');
        $output = ob_get_clean();

        $this->assertSame('0', $this->getDb()->createCommand('SELECT count(*) FROM test_table')->queryScalar());
        $this->assertStringContainsString('    > truncate table test_table ... Done in ', $output);
    }

    public function testAddColumn(): void
    {
        $b = $this->getBuilder();

        ob_start();
        $b->addColumn('test_table', 'code', 'string(4)');
        $output = ob_get_clean();

        $this->assertContains('code', $this->getDb()->getSchema()->getTableSchema('test_table')->getColumnNames());
        $this->assertStringContainsString('    > add column code string(4) to table test_table ... Done in ', $output);
    }

    public function testDropColumn(): void
    {
        $b = $this->getBuilder();

        $this->expectException(NotSupportedException::class);
        $b->dropColumn('test_table', 'code');
    }

    public function testRenameColumn(): void
    {
        $b = $this->getBuilder();

        $this->expectException(NotSupportedException::class);
        $b->renameColumn('test_table', 'id', 'id_new');
    }

    public function testAlterColumn(): void
    {
        $b = $this->getBuilder();

        $this->expectException(NotSupportedException::class);
        $b->alterColumn('test_table', 'id', $b->string());
    }

    public function testAddPrimaryKey(): void
    {
        $b = $this->getBuilder();

        ob_start();
        $b->createTable('test_create_table', ['id2' => $b->integer()]);
        $b->addPrimaryKey('id2', 'test_create_table', ['id2']);
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
        $b = $this->getBuilder();

        $b->createTable('test_create_table', ['id' => $b->primaryKey()]);
        $this->expectException(NotSupportedException::class);
        $b->dropPrimaryKey('id', 'test_create_table');
    }

    public function testAddForeignKey(): void
    {
        $b = $this->getBuilder();

        ob_start();
        $b->createTable('target_table', ['id' => $b->primaryKey()]);
        $b->addForeignKey(
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
        $b = $this->getBuilder();

        ob_start();
        $b->createTable('target_table', ['id2' => $b->primaryKey()]);
        $b->addForeignKey(
            'fk2',
            'test_table',
            'foreign_id',
            'target_table',
            'id2',
            'CASCADE',
            'CASCADE'
        );
        $b->dropForeignKey('fk2', 'test_table');
        $output = ob_get_clean();

        $this->assertStringContainsString(
            '    > Drop foreign key fk2 from table test_table ... Done',
            $output
        );
    }

    public function testCreateIndex(): void
    {
        $b = $this->getBuilder();

        ob_start();
        $b->createIndex('unique_index', 'test_table', 'foreign_id', true);
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
        $b = $this->getBuilder();

        ob_start();
        $b->createIndex('unique_index', 'test_table', 'foreign_id', true);
        $b->dropIndex('unique_index', 'test_table');
        $output = ob_get_clean();

        $this->assertStringContainsString('    > Drop index unique_index on test_table ... Done in ', $output);
    }

    public function testAddCommentOnColumn(): void
    {
        $b = $this->getBuilder();

        $this->expectException(NotSupportedException::class);
        $b->addCommentOnColumn('test_table', 'id', 'test comment');
    }

    public function testAddCommentOnTable(): void
    {
        $b = $this->getBuilder();

        $this->expectException(NotSupportedException::class);
        $b->addCommentOnTable('test_table', 'id');
    }

    public function testDropCommentFromColumn(): void
    {
        $b = $this->getBuilder();

        $this->expectException(NotSupportedException::class);
        $b->dropCommentFromColumn('test_table', 'id');
    }

    public function testDropCommentFromTable(): void
    {
        $b = $this->getBuilder();

        $this->expectException(NotSupportedException::class);
        $b->dropCommentFromTable('test_table');
    }

    public function testMaxSqlOutputLength(): void
    {
        $b = $this->getBuilder(null, 15);

        ob_start();
        $b->execute('SELECT (1+2+3+4+5+6+7+8+9+10+11)');
        $output = ob_get_clean();

        $this->assertMatchesRegularExpression('/.*SEL\[\.\.\. hidden\].*/', $output);
    }

    private function getBuilder(?InformerInterface $informer = null, int $maxSqlOutputLength = 0): MigrationBuilder
    {
        return new MigrationBuilder(
            $this->getDb(),
            $informer ?? $this->getContainer()->get(InformerInterface::class),
            $maxSqlOutputLength
        );
    }
}
