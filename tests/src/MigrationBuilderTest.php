<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests;

use Yiisoft\Db\Exception\IntegrityException;
use Yiisoft\Db\Exception\NotSupportedException;
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
        $informer = new StubMigrationInformer();
        $builder = new MigrationBuilder($this->getDb(), $informer);

        $builder->execute('DROP TABLE test_table');

        $this->assertEmpty($this->getDb()->getSchema()->getTableSchema('test_table'));
        $this->assertStringContainsString('    > Execute SQL: DROP TABLE test_table ... Done in ', $informer->getOutput());
    }

    public function testInsert(): void
    {
        $informer = new StubMigrationInformer();
        $builder = new MigrationBuilder($this->getDb(), $informer);

        $builder->insert('test_table', ['id' => 1]);

        $this->assertEquals(
            '1',
            $this->getDb()->createCommand('SELECT count(*) FROM test_table WHERE id = 1')->queryScalar()
        );
        $this->assertStringContainsString('    > Insert into test_table ... Done in ', $informer->getOutput());
    }

    public function testBatchInsert(): void
    {
        $informer = new StubMigrationInformer();
        $builder = new MigrationBuilder($this->getDb(), $informer);

        $builder->batchInsert('test_table', ['id'], [['id' => 1], ['id' => 2]]);

        $this->assertEquals(
            '2',
            $this->getDb()->createCommand('SELECT count(*) FROM test_table WHERE id IN (1, 2)')->queryScalar()
        );
        $this->assertStringContainsString('    > Insert into test_table ... Done in ', $informer->getOutput());
    }

    public function testUpsert(): void
    {
        $informer = new StubMigrationInformer();
        $builder = new MigrationBuilder($this->getDb(), $informer);

        $builder->insert('test_table', ['id' => 1]);
        $builder->upsert('test_table', ['id' => 1], false);

        $this->assertEquals(
            [
                ['id' => 1],
            ],
            $this->getDb()->createCommand('SELECT id FROM test_table')->queryAll()
        );
        $this->assertStringContainsString('    > Upsert into test_table ... Done in ', $informer->getOutput());
    }

    public function testUpdate(): void
    {
        $informer = new StubMigrationInformer();
        $builder = new MigrationBuilder($this->getDb(), $informer);

        $builder->insert('test_table', ['id' => 1]);
        $builder->update('test_table', ['id' => 2], 'id=:id', ['id' => 1]);

        $this->assertEquals(
            [
                ['id' => 2],
            ],
            $this->getDb()->createCommand('SELECT id FROM test_table')->queryAll()
        );
        $this->assertStringContainsString('    > Update test_table ... Done in ', $informer->getOutput());
    }

    public function testDelete(): void
    {
        $informer = new StubMigrationInformer();
        $builder = new MigrationBuilder($this->getDb(), $informer);

        $builder->insert('test_table', ['id' => 1]);
        $builder->delete('test_table', 'id=:id', ['id' => 1]);

        $this->assertEquals('0', $this->getDb()->createCommand('SELECT count(*) FROM test_table')->queryScalar());
        $this->assertStringContainsString('    > Delete from test_table ... Done in ', $informer->getOutput());
    }

    public function testCreateTable(): void
    {
        $informer = new StubMigrationInformer();
        $builder = new MigrationBuilder($this->getDb(), $informer);

        $builder->createTable('test_create_table', ['id' => $builder->primaryKey()]);

        $this->assertNotEmpty($this->getDb()->getSchema()->getTableSchema('test_create_table'));
        $this->assertStringContainsString('    > create table test_create_table ... Done in ', $informer->getOutput());
    }

    public function testCreateTableWithStringColumnDefinition(): void
    {
        $informer = new StubMigrationInformer();
        $builder = new MigrationBuilder($this->getDb(), $informer);

        $builder->createTable('test_create_table', ['name' => 'varchar(50)']);

        $this->assertNotEmpty($this->getDb()->getSchema()->getTableSchema('test_create_table'));
        $this->assertStringContainsString('    > create table test_create_table ... Done in ', $informer->getOutput());
    }

    public function testRenameTable(): void
    {
        $informer = new StubMigrationInformer();
        $builder = new MigrationBuilder($this->getDb(), $informer);

        $builder->renameTable('test_table', 'new_table');

        $this->assertExistsTables('new_table');
        $this->assertStringContainsString(
            '    > rename table test_table to new_table ... Done in ',
            $informer->getOutput()
        );
    }

    public function testDropTable(): void
    {
        $informer = new StubMigrationInformer();
        $builder = new MigrationBuilder($this->getDb(), $informer);

        $builder->dropTable('test_table');

        $this->assertEmpty($this->getDb()->getSchema()->getTableSchema('test_table'));
        $this->assertStringContainsString('    > Drop table test_table ... Done in ', $informer->getOutput());
    }

    public function testTruncateTable(): void
    {
        $informer = new StubMigrationInformer();
        $builder = new MigrationBuilder($this->getDb(), $informer);
        $builder->insert('test_table', ['foreign_id' => 42]);

        $builder->truncateTable('test_table');

        $this->assertSame('0', $this->getDb()->createCommand('SELECT count(*) FROM test_table')->queryScalar());
        $this->assertStringContainsString('    > truncate table test_table ... Done in ', $informer->getOutput());
    }

    public function testAddColumn(): void
    {
        $informer = new StubMigrationInformer();
        $builder = new MigrationBuilder($this->getDb(), $informer);

        $builder->addColumn('test_table', 'code', 'string(4)');

        $this->assertContains('code', $this->getDb()->getSchema()->getTableSchema('test_table')->getColumnNames());
        $this->assertStringContainsString(
            '    > add column code string(4) to table test_table ... Done in ',
            $informer->getOutput()
        );
    }

    public function testDropColumn(): void
    {
        $informer = new StubMigrationInformer();
        $builder = new MigrationBuilder($this->getDb(), $informer);

        $this->expectException(NotSupportedException::class);
        $builder->dropColumn('test_table', 'code');
    }

    public function testRenameColumn(): void
    {
        $informer = new StubMigrationInformer();
        $builder = new MigrationBuilder($this->getDb(), $informer);

        $this->expectException(NotSupportedException::class);
        $builder->renameColumn('test_table', 'id', 'id_new');
    }

    public function testAlterColumn(): void
    {
        $informer = new StubMigrationInformer();
        $builder = new MigrationBuilder($this->getDb(), $informer);

        $this->expectException(NotSupportedException::class);
        $builder->alterColumn('test_table', 'id', $builder->string());
    }

    public function testAddPrimaryKey(): void
    {
        $informer = new StubMigrationInformer();
        $builder = new MigrationBuilder($this->getDb(), $informer);

        $builder->createTable('test_create_table', ['id2' => $builder->integer()]);
        $builder->addPrimaryKey('id2', 'test_create_table', ['id2']);

        $this->assertTrue(
            $this->getDb()->getSchema()->getTableSchema('test_create_table')->getColumn('id2')->isPrimaryKey()
        );
        $this->assertStringContainsString(
            '    > Add primary key id2 on test_create_table (id2) ... Done in ',
            $informer->getOutput()
        );
    }

    public function testDropPrimaryKey(): void
    {
        $informer = new StubMigrationInformer();
        $builder = new MigrationBuilder($this->getDb(), $informer);

        $builder->createTable('test_create_table', ['id' => $builder->primaryKey()]);
        $this->expectException(NotSupportedException::class);
        $builder->dropPrimaryKey('id', 'test_create_table');
    }

    public function testAddForeignKey(): void
    {
        $informer = new StubMigrationInformer();
        $builder = new MigrationBuilder($this->getDb(), $informer);

        $builder->createTable('target_table', ['id' => $builder->primaryKey()]);
        $builder->addForeignKey(
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
            $informer->getOutput()
        );
    }

    public function testDropForeignKey(): void
    {
        $informer = new StubMigrationInformer();
        $builder = new MigrationBuilder($this->getDb(), $informer);

        $builder->createTable('target_table', ['id2' => $builder->primaryKey()]);
        $builder->addForeignKey(
            'fk2',
            'test_table',
            'foreign_id',
            'target_table',
            'id2',
            'CASCADE',
            'CASCADE'
        );
        $builder->dropForeignKey('fk2', 'test_table');

        $this->assertStringContainsString(
            '    > Drop foreign key fk2 from table test_table ... Done',
            $informer->getOutput()
        );
    }

    public function testCreateIndex(): void
    {
        $informer = new StubMigrationInformer();
        $builder = new MigrationBuilder($this->getDb(), $informer);

        $builder->createIndex('unique_index', 'test_table', 'foreign_id', true);

        $this->assertStringContainsString(
            '    > Create unique index unique_index on test_table (foreign_id) ... Done in ',
            $informer->getOutput()
        );

        $this->getDb()->createCommand()->insert('test_table', ['id' => 1, 'foreign_id' => 1])->execute();
        $this->expectException(IntegrityException::class);
        $this->getDb()->createCommand()->insert('test_table', ['id' => 2, 'foreign_id' => 1])->execute();
    }

    public function testDropIndex(): void
    {
        $informer = new StubMigrationInformer();
        $builder = new MigrationBuilder($this->getDb(), $informer);

        $builder->createIndex('unique_index', 'test_table', 'foreign_id', true);
        $builder->dropIndex('unique_index', 'test_table');

        $this->assertStringContainsString(
            '    > Drop index unique_index on test_table ... Done in ',
            $informer->getOutput()
        );
    }

    public function testAddCommentOnColumn(): void
    {
        $informer = new StubMigrationInformer();
        $builder = new MigrationBuilder($this->getDb(), $informer);

        $this->expectException(NotSupportedException::class);
        $builder->addCommentOnColumn('test_table', 'id', 'test comment');
    }

    public function testAddCommentOnTable(): void
    {
        $informer = new StubMigrationInformer();
        $builder = new MigrationBuilder($this->getDb(), $informer);

        $this->expectException(NotSupportedException::class);
        $builder->addCommentOnTable('test_table', 'id');
    }

    public function testDropCommentFromColumn(): void
    {
        $informer = new StubMigrationInformer();
        $builder = new MigrationBuilder($this->getDb(), $informer);

        $this->expectException(NotSupportedException::class);
        $builder->dropCommentFromColumn('test_table', 'id');
    }

    public function testDropCommentFromTable(): void
    {
        $informer = new StubMigrationInformer();
        $builder = new MigrationBuilder($this->getDb(), $informer);

        $this->expectException(NotSupportedException::class);
        $builder->dropCommentFromTable('test_table');
    }

    public function testMaxSqlOutputLength(): void
    {
        $informer = new StubMigrationInformer();
        $builder = new MigrationBuilder($this->getDb(), $informer, 15);

        $builder->execute('SELECT (1+2+3+4+5+6+7+8+9+10+11)');

        $this->assertMatchesRegularExpression('/.*SEL\[\.\.\. hidden\].*/', $informer->getOutput());
    }
}
