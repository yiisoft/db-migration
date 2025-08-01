<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Common;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Constant\IndexType;
use Yiisoft\Db\Constant\ReferentialAction;
use Yiisoft\Db\Constraint\ForeignKey;
use Yiisoft\Db\Constraint\Index;
use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\Tests\Support\AssertTrait;
use Yiisoft\Db\Migration\Tests\Support\Stub\StubMigrationInformer;
use Yiisoft\Db\Schema\Column\ColumnBuilder;

abstract class AbstractMigrationBuilderTest extends TestCase
{
    use AssertTrait;

    protected ContainerInterface $container;
    protected MigrationBuilder $builder;
    protected ConnectionInterface $db;
    private StubMigrationInformer $informer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareVariables();
    }

    public function testExecute(): void
    {
        $this->builder->createTable('test', ['id' => ColumnBuilder::integer()]);

        $sql = 'DROP TABLE {{test}}';
        $this->builder->execute($sql);

        $sqlOutput = $this->db->getQuoter()->quoteSql($sql);

        $this->assertEmpty($this->db->getTableSchema('test'));
        $this->assertInformerOutputContains("    > Execute SQL: $sqlOutput ... Done in ");
    }

    public function testInsert(): void
    {
        $this->builder->createTable('test', ['id' => ColumnBuilder::integer()]);
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
        $this->builder->createTable('test', ['id' => ColumnBuilder::integer()]);
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
        $this->builder->createTable('test', ['id' => ColumnBuilder::primaryKey(), 'name' => ColumnBuilder::string()]);
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
        $this->builder->createTable('test', ['id' => ColumnBuilder::primaryKey(), 'name' => ColumnBuilder::string()]);
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
        $this->builder->createTable('test', ['id' => ColumnBuilder::integer()]);
        $this->builder->insert('test', ['id' => 1]);
        $this->builder->delete('test', '[[id]]=:id', ['id' => 1]);

        $this->assertSame('0', (string) $this->db->createCommand('SELECT count(*) FROM [[test]]')->queryScalar());
        $this->assertInformerOutputContains('    > Delete from test ... Done in ');

        $this->builder->dropTable('test');
    }

    public function testCreateTable(): void
    {
        $this->builder->createTable('test', ['id' => ColumnBuilder::primaryKey()]);
        $tableSchema = $this->db->getTableSchema('test');
        $column = $tableSchema->getColumn('id');

        $this->assertNotEmpty($tableSchema);
        $this->assertSame('id', $column->getName());
        $this->assertSame('integer', $column->getType());
        $this->assertTrue($column->isPrimaryKey());
        $this->assertTrue($column->isAutoIncrement());
        $this->assertInformerOutputContains('    > create table test ... Done in ');

        $this->builder->dropTable('test');
    }

    public function testCreateTableWithStringColumnDefinition(): void
    {
        $this->builder->createTable('test', ['name' => 'varchar(50)']);
        $tableSchema = $this->db->getTableSchema('test');
        $column = $tableSchema->getColumn('name');

        $this->assertNotEmpty($tableSchema);
        $this->assertSame('name', $column->getName());
        $this->assertSame('string', $column->getType());
        $this->assertSame(50, $column->getSize());
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
    public function testAddColumn(string $type, ?string $expectedComment = null): void
    {
        $expectedOutputString = match ($this->db->getDriverName()) {
            'sqlite' => '    > add column code varchar(4) to table test ... Done in',
            'pgsql' => '    > add column code varchar(4) to table test ... Done in',
            'mysql' => '    > add column code varchar(4) to table test ... Done in',
            'sqlsrv' => '    > add column code nvarchar(4) to table test ... Done in',
            'oci' => '    > add column code varchar2(4) to table test ... Done in',
        };

        if ($type === 'build-string(4)') {
            $type = ColumnBuilder::string(4);
        }

        if ($type === 'build-string(4)-with-comment') {
            $type = ColumnBuilder::string(4)->comment('test comment');

            if ($this->db->getDriverName() === 'mysql') {
                $expectedOutputString = "    > add column code varchar(4) COMMENT 'test comment' to table test ... Done in";
            }
        }

        $this->builder->createTable('test', ['id' => 'int']);
        $this->builder->addColumn('test', 'code', $type);

        $tableSchema = $this->db->getTableSchema('test');
        $column = $tableSchema->getColumn('code');

        $this->assertNotEmpty($tableSchema);
        $this->assertSame('code', $column->getName());
        $this->assertSame('string', $column->getType());
        $this->assertSame(4, $column->getSize());
        $this->assertSame($expectedComment, $column->getComment());
        $this->assertInformerOutputContains($expectedOutputString);

        $this->builder->dropTable('test');
    }

    public function testDropColumn(): void
    {
        $this->builder->createTable('test', ['id' => ColumnBuilder::primaryKey(), 'name' => ColumnBuilder::string()]);
        $this->builder->dropColumn('test', 'name');

        $tableSchema = $this->db->getTableSchema('test');

        $this->assertSame(['id'], $tableSchema->getColumnNames());
        $this->assertInformerOutputContains('    > drop column name from table test ... Done in');

        $this->builder->dropTable('test');
    }

    public function testRenameColumn(): void
    {
        $this->builder->createTable('test', ['id' => ColumnBuilder::integer()]);
        $this->builder->renameColumn('test', 'id', 'id_new');

        $tableSchema = $this->db->getTableSchema('test');

        $this->assertSame(['id_new'], $tableSchema->getColumnNames());
        $this->assertInformerOutputContains('    > Rename column id in table test to id_new ... Done in');

        $this->builder->dropTable('test');
    }

    public static function dataAlterColumn(): array
    {
        return [
            'string-type' => ['string(4)', null, null],
            'string-type-with-default-value' => ['string(4)-defaultValue', 'test', null],
            'builder-type' => ['build-string(4)', null, null],
            'builder-type-with-comment' => ['build-string(4)-with-comment', null, 'test comment'],
        ];
    }

    /**
     * @dataProvider dataAlterColumn
     */
    public function testAlterColumn(string $type, string|null $defaultValue = null, ?string $expectedComment = null): void
    {
        $expectedOutputString = '    > Alter column id in table test to string(4) ... Done in';

        if ($type === 'build-string(4)') {
            $type = ColumnBuilder::string(4);
        }

        if ($type === 'string(4)-defaultValue') {
            $type = ColumnBuilder::string(4)->defaultValue($defaultValue);
            $expectedOutputString = "    > Alter column id in table test to string(4) DEFAULT '$defaultValue' ... Done in";
        }

        if ($type === 'build-string(4)-with-comment') {
            $type = ColumnBuilder::string(4)->comment('test comment');

            if ($this->db->getDriverName() === 'mysql') {
                $expectedOutputString = "    > Alter column id in table test to string(4) COMMENT 'test comment' ... Done in";
            }
        }

        $expectedOutputString = match ($this->db->getDriverName()) {
            'sqlite' => str_replace('string(4)', 'varchar(4)', $expectedOutputString),
            'pgsql' => str_replace('string(4)', 'varchar(4)', $expectedOutputString),
            'mysql' => str_replace('string(4)', 'varchar(4)', $expectedOutputString),
            'sqlsrv' => str_replace('string(4)', 'nvarchar(4)', $expectedOutputString),
            'oci' => str_replace('string(4)', 'varchar2(4)', $expectedOutputString),
        };

        $this->builder->createTable('test', ['id' => ColumnBuilder::integer()]);
        $this->builder->alterColumn('test', 'id', $type);

        $tableSchema = $this->db->getTableSchema('test');
        $column = $tableSchema->getColumn('id');

        $this->assertNotEmpty($tableSchema);
        $this->assertSame('id', $column->getName());
        $this->assertSame('string', $column->getType());
        $this->assertSame(4, $column->getSize());
        $this->assertSame($expectedComment, $column->getComment());

        if ($defaultValue !== null) {
            $this->assertSame($defaultValue, $column->getDefaultValue());
        }

        $this->assertInformerOutputContains($expectedOutputString);

        $this->builder->dropTable('test');
    }

    public function testAddPrimaryKey(): void
    {
        $fieldType = ColumnBuilder::integer();

        if ($this->db->getDriverName() === 'sqlsrv') {
            $fieldType = ColumnBuilder::integer()->notNull();
        }

        $this->builder->createTable('test', ['id' => $fieldType]);
        $this->builder->addPrimaryKey('test', 'id', ['id']);

        $tableSchema = $this->db->getTableSchema('test');
        $column = $tableSchema->getColumn('id');

        $this->assertNotEmpty($tableSchema);
        $this->assertTrue($column->isPrimaryKey());
        $this->assertInformerOutputContains('    > Add primary key id on test (id) ... Done in ');

        $this->builder->dropTable('test');
    }

    public function testDropPrimaryKey(): void
    {
        if ($this->db->getDriverName() === 'sqlite') {
            $this->builder->createTable(
                'test',
                ['id' => 'int CONSTRAINT test_pk PRIMARY KEY', 'name' => ColumnBuilder::string()],
            );
        } else {
            $this->builder->createTable(
                'test',
                ['id' => ColumnBuilder::integer()->notNull(), 'name' => ColumnBuilder::string()],
            );
            $this->builder->addPrimaryKey('test', 'test_pk', 'id');
        }

        $this->builder->dropPrimaryKey('test', 'test_pk');

        $tableSchema = $this->db->getTableSchema('test');
        $column = $tableSchema->getColumn('id');

        $this->assertNotEmpty($tableSchema);
        $this->assertFalse($column->isPrimaryKey());
        $this->assertInformerOutputContains('    > Drop primary key test_pk ... Done in ');

        $this->builder->dropTable('test');
    }

    public function testAddForeignKey(): void
    {
        $this->builder->createTable('target_table', ['id' => 'int unique']);
        $this->builder->createTable(
            'test_table',
            ['id' => ColumnBuilder::integer(), 'foreign_id' => ColumnBuilder::integer()],
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

        $foreingKeys = $this->db->getTableSchema('test_table')->getForeignKeys();

        $defaultSchema = $this->db->getSchema()->getDefaultSchema();
        $onUpdate =  $this->db->getDriverName() !== 'oci' ? ReferentialAction::CASCADE : null;

        $this->assertEquals(
            ['fk' => new ForeignKey('fk', ['foreign_id'], $defaultSchema, 'target_table', ['id'], ReferentialAction::CASCADE, $onUpdate)],
            $foreingKeys,
        );

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

        $foreingKeys = $this->db->getTableSchema('test_table')->getForeignKeys();

        $this->assertEmpty($foreingKeys);
        $this->assertInformerOutputContains('    > Drop foreign key fk from table test_table ... Done');

        $this->builder->dropTable('test_table');
        $this->builder->dropTable('target_table');
    }

    public function testCreateIndex(): void
    {
        $this->builder->createTable('test_table', ['id' => 'int']);
        $this->builder->createIndex('test_table', 'unique_index', 'id', IndexType::UNIQUE);

        $this->assertEquals(
            ['unique_index' => new Index('unique_index', ['id'], true)],
            $this->db->getSchema()->getTableIndexes('test_table'),
        );

        $this->assertInformerOutputContains(
            '    > Create UNIQUE index unique_index on test_table (id) ... Done in ',
        );

        $this->builder->dropTable('test_table');
    }

    public function testCreateAndDropView(): void
    {
        $schema = $this->db->getSchema();

        $this->builder->createTable('test', ['id' => ColumnBuilder::integer()]);
        $this->builder->createView('test_view', 'SELECT * FROM {{test}}');

        $viewNames = $schema->getViewNames(refresh: true);

        $this->assertContains('test_view', $viewNames);
        $this->assertInformerOutputContains('    > Create view test_view ... Done in ');

        $this->builder->dropView('test_view');

        $viewNames = $schema->getViewNames(refresh: true);

        $this->assertNotContains('test_view', $viewNames);
        $this->assertInformerOutputContains('    > Drop view test_view ... Done in ');

        $this->builder->dropTable('test');
    }

    public function testDropIndex(): void
    {
        $this->builder->createTable('test_table', ['id' => ColumnBuilder::integer()]);
        $this->builder->createIndex('test_table', 'test_index', 'id');
        $this->builder->dropIndex('test_table', 'test_index');

        $indexes = $this->db->getSchema()->getTableIndexes('test_table');

        $this->assertCount(0, $indexes);
        $this->assertInformerOutputContains('    > Drop index test_index on test_table ... Done in ');

        $this->builder->dropTable('test_table');
    }

    public function testDropIndexNoExist(): void
    {
        $this->builder->createTable('test_table', ['id' => ColumnBuilder::integer()]);
        $this->builder->dropIndex('test_table', 'test_index');

        $indexes = $this->db->getSchema()->getTableIndexes('test_table');

        $this->assertCount(0, $indexes);
        $this->assertInformerOutputContains('    > Drop index test_index on test_table skipped. Index does not exist.');

        $this->builder->dropTable('test_table');
    }

    public function testDropIndexUnique(): void
    {
        $this->builder->createTable('test_table', ['id' => ColumnBuilder::integer()]);
        $this->builder->createIndex('test_table', 'test_index', 'id', 'UNIQUE');
        $this->builder->dropIndex('test_table', 'test_index');

        $indexes = $this->db->getSchema()->getTableIndexes('test_table');

        $this->assertCount(0, $indexes);
        $this->assertInformerOutputContains('    > Drop index test_index on test_table ... Done in ');

        $this->builder->dropTable('test_table');
    }

    public function testAddCommentOnColumn(): void
    {
        $this->builder->createTable('test_table', ['id' => ColumnBuilder::integer()]);
        $this->builder->addCommentOnColumn('test_table', 'id', 'test comment');

        $tableSchema = $this->db->getTableSchema('test_table');
        $column = $tableSchema->getColumn('id');

        $this->assertSame('test comment', $column->getComment());
        $this->assertInformerOutputContains('    > Add comment on column id ... Done ');

        $this->builder->dropTable('test_table');
    }

    public function testAddCommentOnTable(): void
    {
        $this->builder->createTable('test_table', ['id' => ColumnBuilder::integer()]);
        $this->builder->addCommentOnTable('test_table', 'test comment');

        $tableSchema = $this->builder->getDb()->getTableSchema('test_table');

        $this->assertSame('test comment', $tableSchema?->getComment());
        $this->assertInformerOutputContains('    > Add comment on table test_table ... Done ');

        $this->builder->dropTable('test_table');
    }

    public function testDropCommentFromColumn(): void
    {
        $this->builder->createTable('test_table', ['id' => ColumnBuilder::integer()]);
        $this->builder->addCommentOnColumn('test_table', 'id', 'comment');
        $this->builder->dropCommentFromColumn('test_table', 'id');

        $tableSchema = $this->builder->getDb()->getTableSchema('test_table');
        $column = $tableSchema->getColumn('id');

        match ($this->builder->getDb()->getDriverName()) {
            'mysql' => $this->assertEmpty($column->getComment()),
            default => $this->assertNull($column->getComment()),
        };

        $this->assertInformerOutputContains('    > Drop comment from column id ... Done ');

        $this->builder->dropTable('test_table');
    }

    public function testDropCommentFromTable(): void
    {
        $this->builder->createTable('test_table', ['id' => ColumnBuilder::integer()]);
        $this->builder->addCommentOnTable('test_table', 'comment');
        $this->builder->dropCommentFromTable('test_table');

        $tableSchema = $this->builder->getDb()->getTableSchema('test_table');

        match ($this->builder->getDb()->getDriverName()) {
            'mysql' => $this->assertEmpty($tableSchema?->getComment()),
            default => $this->assertNull($tableSchema?->getComment()),
        };

        $this->builder->dropTable('test_table');
    }

    public function testMaxSqlOutputLength(): void
    {
        $this->prepareVariables(4);

        if ($this->builder->getDb()->getDriverName() === 'oci') {
            $this->builder->execute(
                <<<SQL
                SELECT 1+2+3+4+5+6+7+8+9+10+11 AS resultado FROM dual
                SQL,
            );
        } else {
            $this->builder->execute(
                <<<SQL
                SELECT 1+2+3+4+5+6+7+8+9+10+11
                SQL,
            );
        }

        $this->assertStringContainsString('Execute SQL: SELE [... hidden] ... Done', $this->informer->getOutput());
    }

    public function testGetDb(): void
    {
        $this->assertSame($this->db, $this->builder->getDb());
    }

    protected function assertInformerOutputContains(string $string): void
    {
        $this->assertStringContainsString($string, $this->informer->getOutput());
    }

    private function prepareVariables(int|null $maxSqlOutputLength = null): void
    {
        $this->db = $this->container->get(ConnectionInterface::class);

        $this->informer = new StubMigrationInformer();
        $this->builder = new MigrationBuilder($this->db, $this->informer, $maxSqlOutputLength);
    }
}
