<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Common;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Constraint\IndexConstraint;
use Yiisoft\Db\Pgsql\Column;
use Yiisoft\Yii\Db\Migration\MigrationBuilder;
use Yiisoft\Yii\Db\Migration\Tests\Support\AssertTrait;
use Yiisoft\Yii\Db\Migration\Tests\Support\Helper\DbHelper;
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
        $this->createTable('test', ['id' => 'int']);

        $this->builder->execute('DROP TABLE test');

        $this->assertEmpty($this->db->getSchema()->getTableSchema('test_table'));
        $this->assertInformerOutputContains('    > Execute SQL: DROP TABLE test ... Done in ');
    }

    public function testInsert(): void
    {
        $this->createTable('test', ['id' => 'int']);

        $this->builder->insert('test', ['id' => 1]);

        $this->assertSame(
            '1',
            (string) $this->db->createCommand('SELECT count(*) FROM test WHERE id = 1')->queryScalar()
        );
        $this->assertInformerOutputContains('    > Insert into test ... Done in ');
    }

    public function testBatchInsert(): void
    {
        $this->createTable('test', ['id' => 'int']);

        $this->builder->batchInsert('test', ['id'], [['id' => 1], ['id' => 2]]);

        $this->assertSame(
            '2',
            (string) $this->db->createCommand('SELECT count(*) FROM test WHERE id IN (1, 2)')->queryScalar()
        );
        $this->assertInformerOutputContains('    > Insert into test ... Done in ');
    }

    public function testUpsertWithoutRow(): void
    {
        $this->createTable('test', ['id' => 'int primary key', 'name' => 'string']);
        $this->insert('test', ['id' => 1, 'name' => 'Ivan']);

        $this->builder->upsert('test', ['id' => 1, 'name' => 'Petr'], false);

        $this->assertEquals(
            [
                ['id' => '1', 'name' => 'Ivan'],
            ],
            $this->db->createCommand('SELECT * FROM test')->queryAll()
        );
        $this->assertInformerOutputContains('    > Upsert into test ... Done in ');
    }

    public function testUpdate(): void
    {
        $this->createTable('test', ['id' => 'int primary key', 'name' => 'string']);
        $this->insert('test', ['id' => 1, 'name' => 'Ivan']);

        $this->builder->update('test', ['name' => 'Petr'], 'id=:id', ['id' => 1]);

        $this->assertEquals(
            [
                ['id' => '1', 'name' => 'Petr'],
            ],
            $this->db->createCommand('SELECT * FROM test')->queryAll()
        );
        $this->assertInformerOutputContains('    > Update test ... Done in ');
    }

    public function testDelete(): void
    {
        $this->createTable('test', ['id' => 'int']);
        $this->insert('test', ['id' => 1]);

        $this->builder->delete('test', 'id=:id', ['id' => 1]);

        $this->assertSame('0', (string) $this->db->createCommand('SELECT count(*) FROM test')->queryScalar());
        $this->assertInformerOutputContains('    > Delete from test ... Done in ');
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
    }

    public function testRenameTable(): void
    {
        $this->createTable('test_table', ['id' => 'int']);

        $this->builder->renameTable('test_table', 'new_table');

        $this->assertExistsTables($this->container, 'new_table');
        $this->assertNotExistsTables($this->container, 'test_table');
        $this->assertInformerOutputContains('    > rename table test_table to new_table ... Done in ');
    }

    public function testDropTable(): void
    {
        $this->createTable('test_table', ['id' => 'int']);

        $this->builder->dropTable('test_table');

        $this->assertNotExistsTables($this->container, 'test_table');
        $this->assertInformerOutputContains('    > Drop table test_table ... Done in ');
    }

    public function testTruncateTable(): void
    {
        $this->createTable('test_table', ['id' => 'int']);
        $this->insert('test_table', ['id' => 1]);

        $this->builder->truncateTable('test_table');

        $this->assertSame('0', (string) $this->db->createCommand('SELECT count(*) FROM test_table')->queryScalar());
        $this->assertInformerOutputContains('    > truncate table test_table ... Done in ');
    }

    public static function dataAddColumn(): array
    {
        return [
            'string-type' => ['string(4)', null],
            'builder-type' => [new Column('string', 4), null],
            'builder-type-with-comment' => [
                (new Column('string', 4))->comment('test comment'),
                'test comment',
            ],
        ];
    }

    /**
     * @dataProvider dataAddColumn
     */
    public function testAddColumn($type, string $expectedComment = null): void
    {
        if ($expectedComment === null && in_array($this->db->getDriverName(), ['mysql', 'sqlsrv'], true)) {
            $expectedComment = '';
        }

        $this->createTable('test_table', ['id' => 'int']);

        $this->builder->addColumn('test_table', 'code', $type);

        $schema = $this->db->getSchema()->getTableSchema('test_table')->getColumn('code');
        $this->assertNotEmpty($schema);
        $this->assertSame('code', $schema->getName());
        $this->assertSame('string', $schema->getType());
        $this->assertSame(4, $schema->getSize());
        $this->assertSame($expectedComment, $schema->getComment());
        $this->assertInformerOutputContains('    > add column code string(4) to table test_table ... Done in ');
    }

    public function testDropColumn(): void
    {
        $this->createTable('test', ['id' => 'int primary key', 'name' => 'string']);

        $this->builder->dropColumn('test', 'name');

        $schema = $this->db->getSchema()->getTableSchema('test');
        $this->assertSame(['id'], $schema->getColumnNames());
        $this->assertInformerOutputContains('    > drop column name from table test ... Done in');
    }

    public function testRenameColumn(): void
    {
        $this->createTable('test', ['id' => 'int']);

        $this->builder->renameColumn('test', 'id', 'id_new');

        $schema = $this->db->getSchema()->getTableSchema('test');
        $this->assertSame(['id_new'], $schema->getColumnNames());
        $this->assertInformerOutputContains('    > Rename column id in table test to id_new ... Done in');
    }

    public static function dataAlterColumn(): array
    {
        return [
            'string-type' => ['string(4)', null],
            'builder-type' => [new Column('string', 4), null],
            'builder-type-with-comment' => [
                (new Column('string', 4))->comment('test comment'),
                'test comment',
            ],
        ];
    }

    /**
     * @dataProvider dataAlterColumn
     */
    public function testAlterColumn($type, string $expectedComment = null): void
    {
        if ($expectedComment === null && in_array($this->db->getDriverName(), ['mysql', 'sqlsrv'], true)) {
            $expectedComment = '';
        }

        $this->createTable('test', ['id' => 'int']);

        $this->builder->alterColumn('test', 'id', $type);

        $schema = $this->db->getSchema()->getTableSchema('test')->getColumn('id');
        $this->assertNotEmpty($schema);
        $this->assertSame('id', $schema->getName());
        $this->assertSame('string', $schema->getType());
        $this->assertSame(4, $schema->getSize());
        $this->assertSame($expectedComment, $schema->getComment());
        $this->assertInformerOutputContains('    > Alter column id in table test to string(4) ... Done in');
    }

    public function testAddPrimaryKey(): void
    {
        $fieldType = 'int';

        if ($this->db->getDriverName() === 'sqlsrv') {
            $fieldType = 'int not null';
        }

        $this->createTable('test', ['id' => $fieldType]);

        $this->builder->addPrimaryKey('test', 'id', ['id']);

        $schema = $this->db->getSchema()->getTableSchema('test')->getColumn('id');
        $this->assertNotEmpty($schema);
        $this->assertTrue($schema->isPrimaryKey());
        $this->assertInformerOutputContains('    > Add primary key id on test (id) ... Done in ');
    }

    public function testDropPrimaryKey(): void
    {
        if ($this->db->getDriverName() === 'sqlite') {
            $this->createTable('test', ['id' => 'int CONSTRAINT test_pk PRIMARY KEY', 'name' => 'string']);
        } else {
            $this->createTable('test', ['id' => 'int not null', 'name' => 'string']);
            $this->db->createCommand()->addPrimaryKey('test', 'test_pk', 'id')->execute();
        }

        $this->builder->dropPrimaryKey('test', 'test_pk');

        $schema = $this->db->getSchema()->getTableSchema('test')->getColumn('id');
        $this->assertNotEmpty($schema);
        $this->assertFalse($schema->isPrimaryKey());
        $this->assertInformerOutputContains('    > Drop primary key test_pk ... Done in ');
    }

    public function testAddForeignKey(): void
    {
        $this->createTable('target_table', ['id' => 'int unique']);
        $this->createTable('test_table', ['id' => 'int', 'foreign_id' => 'int']);

        $this->builder->addForeignKey(
            'test_table',
            'fk',
            'foreign_id',
            'target_table',
            'id',
            'CASCADE',
            'CASCADE',
        );

        $keys = $this->db->getSchema()->getTableSchema('test_table')->getForeignKeys();

        $this->assertSame(
            [
                'fk' => ['target_table', 'foreign_id' => 'id'],
            ],
            $keys
        );
        $this->assertInformerOutputContains(
            '    > Add foreign key fk: test_table (foreign_id) references target_table (id) ... Done in',
        );
    }

    public function testDropForeignKey(): void
    {
        $this->createTable('target_table', ['id' => 'int unique']);
        $this->createTable('test_table', ['id' => 'int', 'foreign_id' => 'int']);

        $this->db->createCommand()->addForeignKey('test_table', 'fk', 'foreign_id', 'target_table', 'id')->execute();
        $this->builder->dropForeignKey('test_table', 'fk');

        $keys = $this->db->getSchema()->getTableSchema('test_table')->getForeignKeys();

        $this->assertEmpty($keys);
        $this->assertInformerOutputContains('    > Drop foreign key fk from table test_table ... Done');
    }

    public function testCreateIndex(): void
    {
        $this->createTable('test_table', ['id' => 'int']);

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
    }

    public function testDropIndex(): void
    {
        $this->createTable('test_table', ['id' => 'int']);

        $this->db->createCommand()->createIndex('test_table', 'test_index', 'id')->execute();
        $this->builder->dropIndex('test_table', 'test_index');

        $indexes = $this->db->getSchema()->getTableIndexes('test_table', true);

        $this->assertCount(0, $indexes);
        $this->assertInformerOutputContains('    > Drop index test_index on test_table ... Done in ');
    }

    public function testAddCommentOnColumn(): void
    {
        $this->createTable('test_table', ['id' => 'int']);

        $this->builder->addCommentOnColumn('test_table', 'id', 'test comment');

        $schema = $this->db->getSchema()->getTableSchema('test_table')->getColumn('id');

        $this->assertSame('test comment', $schema->getComment());
    }

    public function testAddCommentOnTable(): void
    {
        $this->createTable('test_table', ['id' => 'int']);

        $this->builder->addCommentOnTable('test_table', 'test comment');

        $tableSchema = $this->db->getSchema()->getTableSchema('test_table', true);

        $this->assertSame('test comment', $tableSchema?->getComment());
    }

    public function testDropCommentFromColumn(): void
    {
        $this->createTable('test_table', ['id' => 'int']);
        $this->db->createCommand()->addCommentOnColumn('test_table', 'id', 'comment')->execute();

        $this->builder->dropCommentFromColumn('test_table', 'id');

        $schema = $this->db->getSchema()->getTableSchema('test_table')->getColumn('id');

        match ($this->db->getDriverName()) {
            'mysql', 'sqlsrv' => $this->assertEmpty($schema->getComment()),
            default => $this->assertNull($schema->getComment()),
        };
    }

    public function testDropCommentFromTable(): void
    {
        $this->createTable('test_table', ['id' => 'int']);
        $this->db->createCommand()->addCommentOnTable('test_table', 'comment')->execute();

        $this->builder->dropCommentFromTable('test_table');

        $tableSchema = $this->db->getSchema()->getTableSchema('test_table', true);

        match ($this->db->getDriverName()) {
            'mysql' => $this->assertEmpty($tableSchema?->getComment()),
            default => $this->assertNull($tableSchema?->getComment()),
        };
    }

    public function testMaxSqlOutputLength(): void
    {
        $builder = new MigrationBuilder($this->db, $this->informer, 15);

        $builder->execute('SELECT (1+2+3+4+5+6+7+8+9+10+11)');

        $this->assertMatchesRegularExpression('/.*SEL\[\.\.\. hidden\].*/', $this->informer->getOutput());
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

    private function createTable(string $name, array $fields): void
    {
        DbHelper::createTable($this->container, $name, $fields);
    }

    private function insert(string $table, array $columns): void
    {
        DbHelper::insert($this->container, $table, $columns);
    }

    private function assertInformerOutputContains(string $string): void
    {
        $this->assertStringContainsString($string, $this->informer->getOutput());
    }

    private function prepareVariables(): void
    {
        $this->informer = new StubMigrationInformer();
        $this->builder = new MigrationBuilder($this->db, $this->informer);
    }
}
