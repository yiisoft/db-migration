<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Constraint\IndexConstraint;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Sqlite\ColumnSchemaBuilder;
use Yiisoft\Yii\Db\Migration\MigrationBuilder;
use Yiisoft\Yii\Db\Migration\Tests\Support\AssertTrait;
use Yiisoft\Yii\Db\Migration\Tests\Support\Helper\DbHelper;
use Yiisoft\Yii\Db\Migration\Tests\Support\Helper\PostgreSqlHelper;
use Yiisoft\Yii\Db\Migration\Tests\Support\Helper\SqLiteHelper;
use Yiisoft\Yii\Db\Migration\Tests\Support\Stub\StubMigrationInformer;

final class MigrationBuilderTest extends TestCase
{
    use AssertTrait;

    private ContainerInterface $container;
    private ConnectionInterface $db;
    private StubMigrationInformer $informer;
    private MigrationBuilder $builder;

    public function testExecute(): void
    {
        $this->prepareSqLite();
        $this->createTable('test', ['id' => 'int']);

        $this->builder->execute('DROP TABLE test');

        $this->assertEmpty($this->db->getSchema()->getTableSchema('test_table'));
        $this->assertInformerOutputContains('    > Execute SQL: DROP TABLE test ... Done in ');
    }

    public function testInsert(): void
    {
        $this->prepareSqLite();
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
        $this->prepareSqLite();
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
        $this->prepareSqLite();
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
        $this->prepareSqLite();
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
        $this->prepareSqLite();
        $this->createTable('test', ['id' => 'int']);
        $this->insert('test', ['id' => 1]);

        $this->builder->delete('test', 'id=:id', ['id' => 1]);

        $this->assertSame('0', (string) $this->db->createCommand('SELECT count(*) FROM test')->queryScalar());
        $this->assertInformerOutputContains('    > Delete from test ... Done in ');
    }

    public function testCreateTable(): void
    {
        $this->prepareSqLite();

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
        $this->prepareSqLite();

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
        $this->prepareSqLite();
        $this->createTable('test_table', ['id' => 'int']);

        $this->builder->renameTable('test_table', 'new_table');

        $this->assertExistsTables($this->container, 'new_table');
        $this->assertNotExistsTables($this->container, 'test_table');
        $this->assertInformerOutputContains('    > rename table test_table to new_table ... Done in ');
    }

    public function testDropTable(): void
    {
        $this->prepareSqLite();
        $this->createTable('test_table', ['id' => 'int']);

        $this->builder->dropTable('test_table');

        $this->assertNotExistsTables($this->container, 'test_table');
        $this->assertInformerOutputContains('    > Drop table test_table ... Done in ');
    }

    public function testTruncateTable(): void
    {
        $this->prepareSqLite();
        $this->createTable('test_table', ['id' => 'int']);
        $this->insert('test_table', ['id' => 1]);

        $this->builder->truncateTable('test_table');

        $this->assertSame('0', (string) $this->db->createCommand('SELECT count(*) FROM test_table')->queryScalar());
        $this->assertInformerOutputContains('    > truncate table test_table ... Done in ');
    }

    public function dataAddColumn(): array
    {
        return [
            'string-type' => ['string(4)', null],
            'builder-type' => [new ColumnSchemaBuilder('string', 4), null],
            'builder-type-with-comment' => [
                (new ColumnSchemaBuilder('string', 4))->comment('test comment'),
                'test comment',
            ],
        ];
    }

    /**
     * @dataProvider dataAddColumn
     */
    public function testAddColumn($type, ?string $expectedComment): void
    {
        $this->preparePostgreSql();
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
        $this->preparePostgreSql();
        $this->createTable('test', ['id' => 'int primary key', 'name' => 'string']);

        $this->builder->dropColumn('test', 'name');

        $schema = $this->db->getSchema()->getTableSchema('test');
        $this->assertSame(['id'], $schema->getColumnNames());
        $this->assertInformerOutputContains('    > drop column name from table test ... Done in');
    }

    public function testDropColumnNotSupported(): void
    {
        $this->prepareSqLite();
        $this->createTable('test', ['id' => 'int primary key', 'name' => 'string']);

        $this->expectException(NotSupportedException::class);
        $this->builder->dropColumn('test', 'name');
    }

    public function testRenameColumn(): void
    {
        $this->preparePostgreSql();
        $this->createTable('test', ['id' => 'int']);

        $this->builder->renameColumn('test', 'id', 'id_new');

        $schema = $this->db->getSchema()->getTableSchema('test');
        $this->assertSame(['id_new'], $schema->getColumnNames());
        $this->assertInformerOutputContains('    > Rename column id in table test to id_new ... Done in');
    }

    public function testRenameColumnNotSupported(): void
    {
        $this->prepareSqLite();
        $this->createTable('test', ['id' => 'int']);

        $this->expectException(NotSupportedException::class);
        $this->builder->renameColumn('test', 'id', 'id_new');
    }

    public function dataAlterColumn(): array
    {
        return [
            'string-type' => ['string(4)', null],
            'builder-type' => [new ColumnSchemaBuilder('string', 4), null],
            'builder-type-with-comment' => [
                (new ColumnSchemaBuilder('string', 4))->comment('test comment'),
                'test comment',
            ],
        ];
    }

    /**
     * @dataProvider dataAlterColumn
     */
    public function testAlterColumn($type, ?string $expectedComment): void
    {
        $this->preparePostgreSql();
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

    public function testAlterColumnNotSupported(): void
    {
        $this->prepareSqLite();
        $this->createTable('test', ['id' => 'int']);

        $this->expectException(NotSupportedException::class);
        $this->builder->alterColumn('test', 'id', 'string');
    }

    public function testAddPrimaryKey(): void
    {
        $this->preparePostgreSql();
        $this->createTable('test', ['id' => 'int']);

        $this->builder->addPrimaryKey('id', 'test', ['id']);

        $schema = $this->db->getSchema()->getTableSchema('test')->getColumn('id');
        $this->assertNotEmpty($schema);
        $this->assertTrue($schema->isPrimaryKey());
        $this->assertInformerOutputContains('    > Add primary key id on test (id) ... Done in ');
    }

    public function testDropPrimaryKey(): void
    {
        $this->preparePostgreSql();
        $this->createTable('test', ['id' => 'int CONSTRAINT test_pk PRIMARY KEY', 'name' => 'string']);

        $this->builder->dropPrimaryKey('test_pk', 'test');

        $schema = $this->db->getSchema()->getTableSchema('test')->getColumn('id');
        $this->assertNotEmpty($schema);
        $this->assertFalse($schema->isPrimaryKey());
        $this->assertInformerOutputContains('    > Drop primary key test_pk ... Done in ');
    }

    public function testDropPrimaryKeyNotSupported(): void
    {
        $this->prepareSqLite();
        $this->createTable('test', ['id' => 'int primary key']);

        $this->expectException(NotSupportedException::class);
        $this->builder->dropPrimaryKey('id', 'test');
    }

    public function testAddForeignKey(): void
    {
        $this->preparePostgreSql();
        $this->createTable('target_table', ['id' => 'int unique']);
        $this->createTable('test_table', ['id' => 'int', 'foreign_id' => 'int']);

        $this->builder->addForeignKey(
            'fk',
            'test_table',
            'foreign_id',
            'target_table',
            'id',
            'CASCADE',
            'CASCADE'
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
        $this->preparePostgreSql();
        $this->createTable('target_table', ['id' => 'int unique']);
        $this->createTable('test_table', ['id' => 'int', 'foreign_id' => 'int']);
        $this->db->createCommand()->addForeignKey('fk', 'test_table', 'foreign_id', 'target_table', 'id')->execute();

        $this->builder->dropForeignKey('fk', 'test_table');

        $keys = $this->db->getSchema()->getTableSchema('test_table')->getForeignKeys();

        $this->assertEmpty($keys);
        $this->assertInformerOutputContains('    > Drop foreign key fk from table test_table ... Done');
    }

    public function testCreateIndex(): void
    {
        $this->prepareSqLite();
        $this->createTable('test_table', ['id' => 'int']);

        $this->builder->createIndex('unique_index', 'test_table', 'id', 'UNIQUE');

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
        $this->prepareSqLite();
        $this->createTable('test_table', ['id' => 'int']);
        $this->db->createCommand()->createIndex('test_index', 'test_table', 'id')->execute();

        $this->builder->dropIndex('test_index', 'test_table');

        $indexes = $this->db->getSchema()->getTableIndexes('test_table', true);

        $this->assertCount(0, $indexes);
        $this->assertInformerOutputContains('    > Drop index test_index on test_table ... Done in ');
    }

    public function testAddCommentOnColumn(): void
    {
        $this->preparePostgreSql();
        $this->createTable('test_table', ['id' => 'int']);

        $this->builder->addCommentOnColumn('test_table', 'id', 'test comment');

        $schema = $this->db->getSchema()->getTableSchema('test_table')->getColumn('id');

        $this->assertSame('test comment', $schema->getComment());
    }

    public function testAddCommentOnColumnNotSupported(): void
    {
        $this->prepareSqLite();
        $this->createTable('test_table', ['id' => 'int']);

        $this->expectException(NotSupportedException::class);
        $this->builder->addCommentOnColumn('test_table', 'id', 'test comment');
    }

    public function testAddCommentOnTable(): void
    {
        $this->preparePostgreSql();
        $this->createTable('test_table', ['id' => 'int']);

        $this->builder->addCommentOnTable('test_table', 'test comment');

        $tableSchema = $this->db->getSchema()->getTableSchema('test_table', true);

        $this->assertSame('test comment', $tableSchema?->getComment());
    }

    public function testAddCommentOnTableNotSupported(): void
    {
        $this->prepareSqLite();
        $this->createTable('test_table', ['id' => 'int']);

        $this->expectException(NotSupportedException::class);
        $this->builder->addCommentOnTable('test_table', 'comment');
    }

    public function testDropCommentFromColumn(): void
    {
        $this->preparePostgreSql();
        $this->createTable('test_table', ['id' => 'int']);
        $this->db->createCommand()->addCommentOnColumn('test_table', 'id', 'comment')->execute();

        $this->builder->dropCommentFromColumn('test_table', 'id');

        $schema = $this->db->getSchema()->getTableSchema('test_table')->getColumn('id');

        $this->assertNull($schema->getComment());
    }

    public function testDropCommentFromColumnNotSupported(): void
    {
        $this->prepareSqLite();
        $this->createTable('test_table', ['id' => 'int']);

        $this->expectException(NotSupportedException::class);
        $this->builder->dropCommentFromColumn('test_table', 'id');
    }

    public function testDropCommentFromTable(): void
    {
        $this->preparePostgreSql();
        $this->createTable('test_table', ['id' => 'int']);
        $this->db->createCommand()->addCommentOnTable('test_table', 'comment')->execute();

        $this->builder->dropCommentFromTable('test_table');

        $tableSchema = $this->db->getSchema()->getTableSchema('test_table', true);

        $this->assertNull($tableSchema?->getComment());
    }

    public function testDropCommentFromTableNotSupported(): void
    {
        $this->prepareSqLite();
        $this->createTable('test_table', ['id' => 'int']);

        $this->expectException(NotSupportedException::class);
        $this->builder->dropCommentFromTable('test_table');
    }

    public function testMaxSqlOutputLength(): void
    {
        $this->prepareSqLite();
        $builder = new MigrationBuilder($this->db, $this->informer, 15);

        $builder->execute('SELECT (1+2+3+4+5+6+7+8+9+10+11)');

        $this->assertMatchesRegularExpression('/.*SEL\[\.\.\. hidden\].*/', $this->informer->getOutput());
    }

    public function testBigInteger(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('bigint', $migrationBuilder->bigInteger()->asString());
    }

    public function testBigPrimaryKey(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('bigpk', $migrationBuilder->bigPrimaryKey()->asString());
    }

    public function testBinary(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('binary', $migrationBuilder->binary()->asString());
    }

    public function testBoolean(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('boolean', $migrationBuilder->boolean()->asString());
    }

    public function testChar(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('char', $migrationBuilder->char()->asString());
    }

    public function testDate(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('date', $migrationBuilder->date()->asString());
    }

    public function testDateTime(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('datetime', $migrationBuilder->dateTime()->asString());
    }

    public function testDecimal(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('decimal', $migrationBuilder->decimal()->asString());
    }

    public function testDouble(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('double', $migrationBuilder->double()->asString());
    }

    public function testFloat(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('float', $migrationBuilder->float()->asString());
    }

    public function testInteger(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('integer', $migrationBuilder->integer()->asString());
    }

    public function testJson(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('json', $migrationBuilder->json()->asString());
    }

    public function testMoney(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('money', $migrationBuilder->money()->asString());
    }

    public function testPrimaryKey(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('pk', $migrationBuilder->primaryKey()->asString());
    }

    public function testSmallInteger(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('smallint', $migrationBuilder->smallInteger()->asString());
    }

    public function testString(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('string', $migrationBuilder->string()->asString());
    }

    public function testText(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('text', $migrationBuilder->text()->asString());
    }

    public function testTime(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('time', $migrationBuilder->time()->asString());
    }

    public function testTimestamp(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('timestamp', $migrationBuilder->timestamp()->asString());
    }

    public function testTinyInteger(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('tinyint', $migrationBuilder->tinyInteger()->asString());
    }

    private function getConnection()
    {
        $this->prepareSqLite();
        return $this->db;
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

    private function prepareSqLite(): void
    {
        $this->container = SqLiteHelper::createContainer();
        SqLiteHelper::clearDatabase($this->container);
        $this->prepareVariables();
    }

    private function preparePostgreSql(): void
    {
        $this->container = PostgreSqlHelper::createContainer();
        PostgreSqlHelper::clearDatabase($this->container);
        $this->prepareVariables();
    }

    private function prepareVariables(): void
    {
        $this->db = $this->container->get(ConnectionInterface::class);
        $this->informer = new StubMigrationInformer();
        $this->builder = new MigrationBuilder($this->db, $this->informer);
    }
}
