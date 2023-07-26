<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Common;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Constraint\IndexConstraint;
use Yiisoft\Yii\Db\Migration\MigrationBuilder;
use Yiisoft\Yii\Db\Migration\Tests\Support\AssertTrait;
use Yiisoft\Yii\Db\Migration\Tests\Support\Stub\StubMigrationInformer;

abstract class AbstractMigrationBuilderTest extends TestCase
{
    use AssertTrait;

    protected ContainerInterface $container;
    protected ConnectionInterface $db;
    private StubMigrationInformer $informer;
    private MigrationBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareVariables();
    }

    public function testExecute(): void
    {
        $this->builder->createTable('test', ['id' => $this->builder->integer()]);
        $this->builder->execute('DROP TABLE {{test}}');

        $this->assertEmpty($this->db->getSchema()->getTableSchema('test_table'));
        $this->assertInformerOutputContains('    > Execute SQL: DROP TABLE {{test}} ... Done in ');
    }

    public function testInsert(): void
    {
        $this->builder->createTable('test', ['id' => $this->builder->integer()]);
        $this->builder->insert('test', ['id' => 1]);

        $this->assertEquals(
            '1',
            $this->db->createCommand('SELECT count(*) FROM {{test}} WHERE [[id]] = 1')->queryScalar()
        );
        $this->assertInformerOutputContains('    > Insert into test ... Done in ');

        $this->builder->dropTable('test');
    }

    public function testBatchInsert(): void
    {
        $this->builder->createTable('test', ['id' => $this->builder->integer()]);
        $this->builder->batchInsert('test', ['id'], [['id' => 1], ['id' => 2]]);

        $this->assertEquals(
            '2',
            $this->db->createCommand('SELECT count(*) FROM {{test}} WHERE [[id]] IN (1, 2)')->queryScalar()
        );
        $this->assertInformerOutputContains('    > Insert into test ... Done in ');

        $this->builder->dropTable('test');
    }

    public function testUpsertWithoutRow(): void
    {
        $this->builder->createTable('test', ['id' => $this->builder->primaryKey(), 'name' => $this->builder->string()]);
        $this->builder->insert('test', ['name' => 'Ivan']);
        $this->builder->upsert('test', ['name' => 'Petr'], false);

        $this->assertEquals(
            [
                ['id' => 1, 'name' => 'Ivan'],
                ['id' => 2, 'name' => 'Petr'],
            ],
            $this->db->createCommand('SELECT * FROM {{test}}')->queryAll()
        );
        $this->assertInformerOutputContains('    > Upsert into test ... Done in ');

        $this->builder->dropTable('test');
    }

    public function testUpdate(): void
    {
        $this->builder->createTable('test', ['id' => $this->builder->primaryKey(), 'name' => $this->builder->string()]);
        $this->builder->insert('test', ['name' => 'Ivan']);
        $this->builder->update('test', ['name' => 'Petr'], '[[id]]=:id', ['id' => 1]);

        $this->assertEquals(
            [
                ['id' => '1', 'name' => 'Petr'],
            ],
            $this->db->createCommand('SELECT * FROM {{test}}')->queryAll()
        );
        $this->assertInformerOutputContains('    > Update test ... Done in ');

        $this->builder->dropTable('test');
    }

    public function testDelete(): void
    {
        $this->builder->createTable('test', ['id' => $this->builder->integer()]);
        $this->builder->insert('test', ['id' => 1]);
        $this->builder->delete('test', '[[id]]=:id', ['id' => 1]);

        $this->assertSame('0', (string) $this->db->createCommand('SELECT count(*) FROM [[test]]')->queryScalar());
        $this->assertInformerOutputContains('    > Delete from test ... Done in ');

        $this->builder->dropTable('test');
    }

    public function testCreateTable(): void
    {
        $this->builder->createTable('test', ['id' => $this->builder->primaryKey()]);
        $schema = $this->db->getSchema()->getTableSchema('test');

        $this->assertNotEmpty($schema);
        $this->assertSame('id', $schema->getColumn('id')->getName());
        $this->assertSame('integer', $schema->getColumn('id')->getType());
        $this->assertTrue($schema->getColumn('id')->isPrimaryKey());
        $this->assertTrue($schema->getColumn('id')->isAutoIncrement());
        $this->assertInformerOutputContains('    > create table test ... Done in ');

        $this->builder->dropTable('test');
    }

    public function testCreateTableWithStringColumnDefinition(): void
    {
        $this->builder->createTable('test', ['name' => 'varchar(50)']);
        $schema = $this->db->getSchema()->getTableSchema('test');

        $this->assertNotEmpty($schema);
        $this->assertSame('name', $schema->getColumn('name')->getName());
        $this->assertSame('string', $schema->getColumn('name')->getType());
        $this->assertSame(50, $schema->getColumn('name')->getSize());

        $this->assertInformerOutputContains('    > create table test ... Done in ');

        $this->builder->dropTable('test');
    }

    public function testRenameTable(): void
    {
        $this->builder->createTable('test', ['id' => 'int']);
        $this->builder->renameTable('test', 'new_table');

        $this->assertExistsTables($this->container, 'new_table');
        $this->assertNotExistsTables($this->container, 'test');
        $this->assertInformerOutputContains('    > rename table test to new_table ... Done in ');

        $this->builder->dropTable('new_table');
    }

    public function testDropTable(): void
    {
        $this->builder->createTable('test', ['id' => 'int']);
        $this->builder->dropTable('test');

        $this->assertNotExistsTables($this->container, 'test');
        $this->assertInformerOutputContains('    > Drop table test ... Done in ');
    }

    public function testTruncateTable(): void
    {
        $this->builder->createTable('test', ['id' => 'int']);
        $this->builder->insert('test', ['id' => 1]);
        $this->builder->truncateTable('test');

        $this->assertEquals('0', $this->db->createCommand('SELECT count(*) FROM {{test}}')->queryScalar());
        $this->assertInformerOutputContains('    > truncate table test ... Done in ');

        $this->builder->dropTable('test');
    }

    public static function dataAddColumn(): array
    {
        return [
            'string-type' => ['string(4)', null],
            'builder-type' => ['build-string(4)', null],
            'builder-type-with-comment' => [
                'build-string(4)-with-comment',
                'test comment',
            ],
        ];
    }

    /**
     * @dataProvider dataAddColumn
     */
    public function testAddColumn($type, string $expectedComment = null): void
    {
        $expectedOutputString = '    > add column code string(4) to table test ... Done in';

        if ($type === 'build-string(4)') {
            $type = $this->builder->string(4);
        }

        if ($type === 'build-string(4)-with-comment') {
            $type = $this->builder->string(4)->comment('test comment');

            if ($this->db->getDriverName() === 'mysql') {
                $expectedOutputString = "    > add column code string(4) COMMENT 'test comment' to table test ... Done in";
            }
        }

        if ($expectedComment === null && in_array($this->db->getDriverName(), ['mysql', 'sqlsrv'], true)) {
            $expectedComment = '';
        }

        $this->builder->createTable('test', ['id' => 'int']);
        $this->builder->addColumn('test', 'code', $type);

        $tableSchema = $this->db->getSchema()->getTableSchema('test', true)->getColumn('code');

        $this->assertNotEmpty($tableSchema);
        $this->assertSame('code', $tableSchema->getName());
        $this->assertSame('string', $tableSchema->getType());
        $this->assertSame(4, $tableSchema->getSize());
        $this->assertSame($expectedComment, $tableSchema->getComment());
        $this->assertInformerOutputContains($expectedOutputString);

        $this->builder->dropTable('test');
    }

    public function testDropColumn(): void
    {
        $this->builder->createTable('test', ['id' => $this->builder->primaryKey(), 'name' => $this->builder->string()]);
        $this->builder->dropColumn('test', 'name');

        $tableSchema = $this->db->getSchema()->getTableSchema('test', true);

        $this->assertSame(['id'], $tableSchema->getColumnNames());
        $this->assertInformerOutputContains('    > drop column name from table test ... Done in');

        $this->builder->dropTable('test');
    }

    public function testRenameColumn(): void
    {
        $this->builder->createTable('test', ['id' => $this->builder->integer()]);
        $this->builder->renameColumn('test', 'id', 'id_new');

        $tableSchema = $this->db->getSchema()->getTableSchema('test', true);

        $this->assertSame(['id_new'], $tableSchema->getColumnNames());
        $this->assertInformerOutputContains('    > Rename column id in table test to id_new ... Done in');

        $this->builder->dropTable('test');
    }

    public static function dataAlterColumn(): array
    {
        return [
            'string-type' => ['string(4)', null],
            'builder-type' => ['build-string(4)', null],
            'builder-type-with-comment' => [
                'build-string(4)-with-comment',
                'test comment',
            ],
        ];
    }

    /**
     * @dataProvider dataAlterColumn
     */
    public function testAlterColumn($type, string $expectedComment = null): void
    {
        $expectedOutputString = '    > Alter column id in table test to string(4) ... Done in';

        if ($type === 'build-string(4)') {
            $type = $this->builder->string(4);
        }

        if ($type === 'build-string(4)-with-comment') {
            $type = $this->builder->string(4)->comment('test comment');

            if ($this->db->getDriverName() === 'mysql') {
                $expectedOutputString = "    > Alter column id in table test to string(4) COMMENT 'test comment' ... Done in";
            }
        }

        if ($expectedComment === null && in_array($this->db->getDriverName(), ['mysql', 'sqlsrv'], true)) {
            $expectedComment = '';
        }

        $this->builder->createTable('test', ['id' => $this->builder->integer()]);
        $this->builder->alterColumn('test', 'id', $type);

        $tableSchema = $this->db->getSchema()->getTableSchema('test', true)->getColumn('id');

        $this->assertNotEmpty($tableSchema);
        $this->assertSame('id', $tableSchema->getName());
        $this->assertSame('string', $tableSchema->getType());
        $this->assertSame(4, $tableSchema->getSize());
        $this->assertSame($expectedComment, $tableSchema->getComment());

        $this->assertInformerOutputContains($expectedOutputString);

        $this->builder->dropTable('test');
    }

    public function testAddPrimaryKey(): void
    {
        $fieldType = $this->builder->integer();

        if ($this->db->getDriverName() === 'sqlsrv') {
            $fieldType = $this->builder->integer()->notNull();
        }

        $this->builder->createTable('test', ['id' => $fieldType]);
        $this->builder->addPrimaryKey('test', 'id', ['id']);

        $tableSchema = $this->db->getSchema()->getTableSchema('test', true)->getColumn('id');

        $this->assertNotEmpty($tableSchema);
        $this->assertTrue($tableSchema->isPrimaryKey());
        $this->assertInformerOutputContains('    > Add primary key id on test (id) ... Done in ');

        $this->builder->dropTable('test');
    }

    public function testDropPrimaryKey(): void
    {
        if ($this->db->getDriverName() === 'sqlite') {
            $this->builder->createTable(
                'test',
                ['id' => 'int CONSTRAINT test_pk PRIMARY KEY', 'name' => $this->builder->string()],
            );
        } else {
            $this->builder->createTable(
                'test',
                ['id' => $this->builder->integer()->notNull(), 'name' => $this->builder->string()],
            );
            $this->builder->addPrimaryKey('test', 'test_pk', 'id');
        }

        $this->builder->dropPrimaryKey('test', 'test_pk');

        $tableSchema = $this->db->getSchema()->getTableSchema('test', true)->getColumn('id');

        $this->assertNotEmpty($tableSchema);
        $this->assertFalse($tableSchema->isPrimaryKey());
        $this->assertInformerOutputContains('    > Drop primary key test_pk ... Done in ');

        $this->builder->dropTable('test');
    }

    public function testAddForeignKey(): void
    {
        $this->builder->createTable('target_table', ['id' => 'int unique']);
        $this->builder->createTable(
            'test_table',
            ['id' => $this->builder->integer(), 'foreign_id' => $this->builder->integer()],
        );

        $update = 'CASCADE';

        if ($this->db->getDriverName() === 'oci') {
            // Oracle does not support ON UPDATE.
            $update = null;
        }

        $this->builder->addForeignKey(
            'test_table',
            'fk',
            'foreign_id',
            'target_table',
            'id',
            'CASCADE',
            $update,
        );

        $foreingKeys = $this->db->getSchema()->getTableSchema('test_table')->getForeignKeys();

        if ($this->db->getDriverName() !== 'oci') {
            $this->assertSame(['fk' => ['target_table', 'foreign_id' => 'id']], $foreingKeys);
        } else {
            $this->assertSame([['target_table', 'foreign_id' => 'id']], $foreingKeys);
        }

        $this->assertInformerOutputContains(
            '    > Add foreign key fk: test_table (foreign_id) references target_table (id) ... Done in',
        );

        $this->builder->dropTable('test_table');
        $this->builder->dropTable('target_table');
    }

    public function testDropForeignKey(): void
    {
        $this->builder->createTable('target_table', ['id' => 'int unique']);
        $this->builder->createTable('test_table', ['id' => 'int', 'foreign_id' => 'int']);
        $this->builder->addForeignKey('test_table', 'fk', 'foreign_id', 'target_table', 'id');
        $this->builder->dropForeignKey('test_table', 'fk');

        $foreingKeys = $this->db->getSchema()->getTableSchema('test_table')->getForeignKeys();

        $this->assertEmpty($foreingKeys);
        $this->assertInformerOutputContains('    > Drop foreign key fk from table test_table ... Done');

        $this->builder->dropTable('target_table');
        $this->builder->dropTable('test_table');
    }

    public function testCreateIndex(): void
    {
        $this->builder->createTable('test_table', ['id' => 'int']);
        $this->builder->createIndex('test_table', 'unique_index', 'id', 'UNIQUE');

        $indexes = $this->db->getSchema()->getTableIndexes('test_table', true);

        $this->assertCount(1, $indexes);

        /** @var IndexConstraint $index */
        $index = $indexes[0];

        $this->assertSame('unique_index', $index->getName());
        $this->assertSame(['id'], $index->getColumnNames());
        $this->assertTrue($index->isUnique());
        $this->assertFalse($index->isPrimary());

        $this->assertInformerOutputContains(
            '    > Create UNIQUE index unique_index on test_table (id) ... Done in ',
        );

        $this->builder->dropTable('test_table');
    }

    public function testDropIndex(): void
    {
        $this->builder->createTable('test_table', ['id' => $this->builder->integer()]);
        $this->builder->createIndex('test_table', 'test_index', 'id');
        $this->builder->dropIndex('test_table', 'test_index');

        $indexes = $this->db->getSchema()->getTableIndexes('test_table', true);

        $this->assertCount(0, $indexes);
        $this->assertInformerOutputContains('    > Drop index test_index on test_table ... Done in ');

        $this->builder->dropTable('test_table');
    }

    public function testAddCommentOnColumn(): void
    {
        $this->builder->createTable('test_table', ['id' => $this->builder->integer()]);
        $this->builder->addCommentOnColumn('test_table', 'id', 'test comment');

        $column = $this->db->getSchema()->getTableSchema('test_table')->getColumn('id');

        $this->assertSame('test comment', $column->getComment());
        $this->assertInformerOutputContains('    > Add comment on column id ... Done ');

        $this->builder->dropTable('test_table');
    }

    public function testAddCommentOnTable(): void
    {
        $this->builder->createTable('test_table', ['id' => $this->builder->integer()]);
        $this->builder->addCommentOnTable('test_table', 'test comment');

        $tableSchema = $this->db->getSchema()->getTableSchema('test_table', true);

        $this->assertSame('test comment', $tableSchema?->getComment());
    }

    public function testDropCommentFromColumn(): void
    {
        $this->builder->createTable('test_table', ['id' => $this->builder->integer()]);
        $this->builder->addCommentOnColumn('test_table', 'id', 'comment');
        $this->builder->dropCommentFromColumn('test_table', 'id');

        $column = $this->db->getSchema()->getTableSchema('test_table')->getColumn('id');

        match ($this->db->getDriverName()) {
            'mysql', 'oci', 'sqlsrv' => $this->assertEmpty($column->getComment()),
            default => $this->assertNull($column->getComment()),
        };
    }

    public function testDropCommentFromTable(): void
    {
        $this->builder->createTable('test_table', ['id' => $this->builder->integer()]);
        $this->builder->addCommentOnTable('test_table', 'comment');
        $this->builder->dropCommentFromTable('test_table');

        $tableSchema = $this->db->getSchema()->getTableSchema('test_table', true);

        match ($this->db->getDriverName()) {
            'mysql' => $this->assertEmpty($tableSchema?->getComment()),
            default => $this->assertNull($tableSchema?->getComment()),
        };
    }

    public function testMaxSqlOutputLength(): void
    {
        $this->prepareVariables(4);

        if ($this->db->getDriverName() === 'oci') {
            $this->builder->execute(
                <<<SQL
                SELECT 1+2+3+4+5+6+7+8+9+10+11 AS resultado FROM dual
                SQL,
            );
            $expected = 'Execute SQL: SELECT 1+2+3+4+5+6+7+8+9+10+11 AS resultado F[... hidden] ... Done';
        } else {
            $this->builder->execute(
                <<<SQL
                SELECT 1+2+3+4+5+6+7+8+9+10+11
                SQL,
            );
            $expected = 'Execute SQL: SELECT 1+2+3+4+5+6+7+8[... hidden] ... Done';
        }

        $this->assertStringContainsString($expected, $this->informer->getOutput());
    }

    public function testBigInteger(): void
    {
        $this->assertSame('bigint', $this->builder->bigInteger()->asString());
    }

    public function testBigPrimaryKey(): void
    {
        $this->assertSame('bigpk', $this->builder->bigPrimaryKey()->asString());
    }

    public function testBinary(): void
    {
        $this->assertSame('binary', $this->builder->binary()->asString());
    }

    public function testBoolean(): void
    {
        $this->assertSame('boolean', $this->builder->boolean()->asString());
    }

    public function testChar(): void
    {
        $this->assertSame('char', $this->builder->char()->asString());
    }

    public function testDate(): void
    {
        $this->assertSame('date', $this->builder->date()->asString());
    }

    public function testDateTime(): void
    {
        $this->assertSame('datetime', $this->builder->dateTime()->asString());
    }

    public function testDecimal(): void
    {
        $this->assertSame('decimal', $this->builder->decimal()->asString());
    }

    public function testDecimalWithPrecisionAndScale(): void
    {
        $this->assertSame('decimal(10,2)', $this->builder->decimal(10, 2)->asString());
    }

    public function testDouble(): void
    {
        $this->assertSame('double', $this->builder->double()->asString());
    }

    public function testFloat(): void
    {
        $this->assertSame('float', $this->builder->float()->asString());
    }

    public function testInteger(): void
    {
        $this->assertSame('integer', $this->builder->integer()->asString());
    }

    public function testJson(): void
    {
        $this->assertSame('json', $this->builder->json()->asString());
    }

    public function testMoney(): void
    {
        $this->assertSame('money', $this->builder->money()->asString());
    }

    public function testMoneyWithPrecisionAndScale(): void
    {
        $this->assertSame('money(10,2)', $this->builder->money(10, 2)->asString());
    }

    public function testPrimaryKey(): void
    {
        $this->assertSame('pk', $this->builder->primaryKey()->asString());
    }

    public function testSmallInteger(): void
    {
        $this->assertSame('smallint', $this->builder->smallInteger()->asString());
    }

    public function testString(): void
    {
        $this->assertSame('string', $this->builder->string()->asString());
    }

    public function testText(): void
    {
        $this->assertSame('text', $this->builder->text()->asString());
    }

    public function testTime(): void
    {
        $this->assertSame('time', $this->builder->time()->asString());
    }

    public function testTimestamp(): void
    {
        $this->assertSame('timestamp', $this->builder->timestamp()->asString());
    }

    public function testTinyInteger(): void
    {
        $this->assertSame('tinyint', $this->builder->tinyInteger()->asString());
    }

    public function testGetDb(): void
    {
        $this->assertSame($this->db, $this->builder->getDb());
    }

    private function assertInformerOutputContains(string $string): void
    {
        $this->assertStringContainsString($string, $this->informer->getOutput());
    }

    private function prepareVariables(int $maxSqlOutputLength = 0): void
    {
        $this->informer = new StubMigrationInformer();
        $this->builder = new MigrationBuilder($this->db, $this->informer, $maxSqlOutputLength);
    }
}
