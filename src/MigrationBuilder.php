<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration;

use Exception;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Schema\ColumnSchemaBuilder;
use Yiisoft\Db\Schema\SchemaBuilderTrait;
use Yiisoft\Strings\StringHelper;

final class MigrationBuilder
{
    use SchemaBuilderTrait;

    private ConnectionInterface $db;
    private bool $compact;
    private int $maxSqlOutputLength;

    public function __construct(
        ConnectionInterface $db,
        bool $compact = false,
        int $maxSqlOutputLength = 0
    ) {
        $this->db = $db;
        $this->db->getSchema()->refresh();
        $this->db->setEnableSlaves(false);

        $this->compact = $compact;
        $this->maxSqlOutputLength = $maxSqlOutputLength;
    }

    public function getDb(): ConnectionInterface
    {
        return $this->db;
    }

    /**
     * Executes a SQL statement.
     *
     * This method executes the specified SQL statement using {@see ConnectionInterface}.
     *
     * @param string $sql the SQL statement to be executed
     * @param array $params input parameters (name => value) for the SQL execution.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * {See \Yiisoft\Db\Command\Command::execute()} for more details.
     */
    public function execute(string $sql, array $params = []): void
    {
        $sqlOutput = $sql;
        if ($this->maxSqlOutputLength > 0) {
            $sqlOutput = StringHelper::truncateEnd($sql, $this->maxSqlOutputLength, '[... hidden]');
        }

        $time = $this->beginCommand("Execute SQL: $sqlOutput");
        $this->db->createCommand($sql)->bindValues($params)->execute();
        $this->endCommand($time);
    }

    /**
     * Creates and executes an INSERT SQL statement.
     *
     * The method will properly escape the column names, and bind the values to be inserted.
     *
     * @param string $table the table that new rows will be inserted into.
     * @param array $columns the column data (name => value) to be inserted into the table.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function insert(string $table, array $columns): void
    {
        $time = $this->beginCommand("Insert into $table");
        $this->db->createCommand()->insert($table, $columns)->execute();
        $this->endCommand($time);
    }

    /**
     * Creates and executes a batch INSERT SQL statement.
     *
     * The method will properly escape the column names, and bind the values to be inserted.
     *
     * @param string $table the table that new rows will be inserted into.
     * @param array $columns the column names.
     * @param array $rows the rows to be batch inserted into the table
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function batchInsert(string $table, array $columns, array $rows): void
    {
        $time = $this->beginCommand("Insert into $table");
        $this->db->createCommand()->batchInsert($table, $columns, $rows)->execute();
        $this->endCommand($time);
    }

    /**
     * Creates and executes a command to insert rows into a database table if they do not already exist (matching unique
     * constraints), or update them if they do.
     *
     * The method will properly escape the column names, and bind the values to be inserted.
     *
     * @param string $table the table that new rows will be inserted into/updated in.
     * @param array|Query $insertColumns the column data (name => value) to be inserted into the table or instance
     * of {@see Query} to perform `INSERT INTO ... SELECT` SQL statement.
     * @param array|bool $updateColumns the column data (name => value) to be updated if they already exist.
     * If `true` is passed, the column data will be updated to match the insert column data.
     * If `false` is passed, no update will be performed if the column data already exists.
     * @param array $params the parameters to be bound to the command.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function upsert(string $table, $insertColumns, $updateColumns = true, array $params = []): void
    {
        $time = $this->beginCommand("Upsert into $table");
        $this->db->createCommand()->upsert($table, $insertColumns, $updateColumns, $params)->execute();
        $this->endCommand($time);
    }

    /**
     * Creates and executes an UPDATE SQL statement.
     *
     * The method will properly escape the column names and bind the values to be updated.
     *
     * @param string $table the table to be updated.
     * @param array $columns the column data (name => value) to be updated.
     * @param array|string $condition the conditions that will be put in the WHERE part. Please refer to
     * {@see \Yiisoft\Db\Query\Query::where()} on how to specify conditions.
     * @param array $params the parameters to be bound to the query.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function update(string $table, array $columns, $condition = '', array $params = []): void
    {
        $time = $this->beginCommand("Update $table");
        $this->db->createCommand()->update($table, $columns, $condition, $params)->execute();
        $this->endCommand($time);
    }

    /**
     * Creates and executes a DELETE SQL statement.
     *
     * @param string $table the table where the data will be deleted from.
     * @param array|string $condition the conditions that will be put in the WHERE part. Please refer to
     * {@see \Yiisoft\Db\Query\Query::where()} on how to specify conditions.
     * @param array $params the parameters to be bound to the query.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function delete(string $table, $condition = '', array $params = []): void
    {
        $time = $this->beginCommand("Delete from $table");
        $this->db->createCommand()->delete($table, $condition, $params)->execute();
        $this->endCommand($time);
    }

    /**
     * Builds and executes a SQL statement for creating a new DB table.
     *
     * The columns in the new  table should be specified as name-definition pairs (e.g. 'name' => 'string'), where name
     * stands for a column name which will be properly quoted by the method, and definition stands for the column type
     * which can contain an abstract DB type.
     *
     * The {@see \Yiisoft\Db\Query\QueryBuilder::getColumnType()} method will be invoked to convert any abstract type
     * into a physical one.
     *
     * If a column is specified with definition only (e.g. 'PRIMARY KEY (name, type)'), it will be directly put into the
     * generated SQL.
     *
     * @param string $table the name of the table to be created. The name will be properly quoted by the method.
     * @param array $columns the columns (name => definition) in the new table.
     * @param string|null $options additional SQL fragment that will be appended to the generated SQL.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function createTable(string $table, array $columns, ?string $options = null): void
    {
        $time = $this->beginCommand("create table $table");

        $this->db->createCommand()->createTable($table, $columns, $options)->execute();

        foreach ($columns as $column => $type) {
            if ($type instanceof ColumnSchemaBuilder) {
                $comment = $type->getComment();
                if ($comment !== null) {
                    $this->db->createCommand()->addCommentOnColumn($table, $column, $comment)->execute();
                }
            }
        }

        $this->endCommand($time);
    }

    /**
     * Builds and executes a SQL statement for renaming a DB table.
     *
     * @param string $table the table to be renamed. The name will be properly quoted by the method.
     * @param string $newName the new table name. The name will be properly quoted by the method.
     */
    public function renameTable(string $table, string $newName): void
    {
        $time = $this->beginCommand("rename table $table to $newName");
        $this->db->createCommand()->renameTable($table, $newName)->execute();
        $this->endCommand($time);
    }

    /**
     * Builds and executes a SQL statement for dropping a DB table.
     *
     * @param string $table the table to be dropped. The name will be properly quoted by the method.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function dropTable(string $table): void
    {
        $time = $this->beginCommand("Drop table $table");
        $this->db->createCommand()->dropTable($table)->execute();
        $this->endCommand($time);
    }

    /**
     * Builds and executes a SQL statement for truncating a DB table.
     *
     * @param string $table the table to be truncated. The name will be properly quoted by the method.
     */
    public function truncateTable(string $table): void
    {
        $time = $this->beginCommand("truncate table $table");
        $this->db->createCommand()->truncateTable($table)->execute();
        $this->endCommand($time);
    }

    /**
     * Builds and executes a SQL statement for adding a new DB column.
     *
     * @param string $table the table that the new column will be added to.
     * The table name will be properly quoted by the method.
     * @param string $column the name of the new column. The name will be properly quoted by the method.
     * @param ColumnSchemaBuilder|string $type the column type. The {@see QueryBuilder::getColumnType()} method
     * will be invoked to convert abstract column type (if any) into the physical one. Anything that is not
     * recognized as abstract type will be kept in the generated SQL. For example, 'string' will be turned
     * into 'varchar(255)', while 'string not null' will become 'varchar(255) not null'.
     */
    public function addColumn(string $table, string $column, $type): void
    {
        $comment = null;
        if ($type instanceof ColumnSchemaBuilder) {
            $comment = $type->getComment();
            $type = $type->__toString();
        }

        $time = $this->beginCommand("add column $column $type to table $table");
        $this->db->createCommand()->addColumn($table, $column, $type)->execute();
        if ($comment !== null) {
            $this->db->createCommand()->addCommentOnColumn($table, $column, $comment)->execute();
        }
        $this->endCommand($time);
    }

    /**
     * Builds and executes a SQL statement for dropping a DB column.
     *
     * @param string $table the table whose column is to be dropped. The name will be properly quoted by the method.
     * @param string $column the name of the column to be dropped. The name will be properly quoted by the method.
     */
    public function dropColumn(string $table, string $column): void
    {
        $time = $this->beginCommand("drop column $column from table $table");
        $this->db->createCommand()->dropColumn($table, $column)->execute();
        $this->endCommand($time);
    }

    /**
     * Builds and executes a SQL statement for renaming a column.
     *
     * @param string $table the table whose column is to be renamed. The name will be properly quoted by the method.
     * @param string $name the old name of the column. The name will be properly quoted by the method.
     * @param string $newName the new name of the column. The name will be properly quoted by the method.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function renameColumn(string $table, string $name, string $newName): void
    {
        $time = $this->beginCommand("Rename column $name in table $table to $newName");
        $this->db->createCommand()->renameColumn($table, $name, $newName)->execute();
        $this->endCommand($time);
    }

    /**
     * Builds and executes a SQL statement for changing the definition of a column.
     *
     * @param string $table the table whose column is to be changed. The table name will be properly quoted by the
     * method.
     * @param string $column the name of the column to be changed. The name will be properly quoted by the method.
     * @param ColumnSchemaBuilder|string $type the new column type.
     * The {@see \Yiisoft\Db\Query\QueryBuilder::getColumnType()} method will be invoked to convert abstract column
     * type (if any) into the physical one. Anything that is not recognized as abstract type will be kept in the
     * generated SQL. For example, 'string' will be turned into 'varchar(255)', while 'string not null' will become
     * 'varchar(255) not null'.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function alterColumn(string $table, string $column, $type): void
    {
        $comment = null;

        if ($type instanceof ColumnSchemaBuilder) {
            $comment = $type->getComment();
            $type = $type->__toString();
        }

        $time = $this->beginCommand("Alter column $column in table $table to $type");

        $this->db->createCommand()->alterColumn($table, $column, $type)->execute();

        if ($comment !== null) {
            $this->db->createCommand()->addCommentOnColumn($table, $column, $comment)->execute();
        }

        $this->endCommand($time);
    }

    /**
     * Builds and executes a SQL statement for creating a primary key.
     *
     * The method will properly quote the table and column names.
     *
     * @param string $name the name of the primary key constraint.
     * @param string $table the table that the primary key constraint will be added to.
     * @param array|string $columns comma separated string or array of columns that the primary key will consist of.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function addPrimaryKey(string $name, string $table, $columns): void
    {
        $time = $this->beginCommand(
            "Add primary key $name on $table (" . (is_array($columns) ? implode(',', $columns) : $columns) . ')'
        );
        $this->db->createCommand()->addPrimaryKey($name, $table, $columns)->execute();
        $this->endCommand($time);
    }

    /**
     * Builds and executes a SQL statement for dropping a primary key.
     *
     * @param string $name the name of the primary key constraint to be removed.
     * @param string $table the table that the primary key constraint will be removed from.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function dropPrimaryKey(string $name, string $table): void
    {
        $time = $this->beginCommand("Drop primary key $name");
        $this->db->createCommand()->dropPrimaryKey($name, $table)->execute();
        $this->endCommand($time);
    }

    /**
     * Builds a SQL statement for adding a foreign key constraint to an existing table.
     *
     * The method will properly quote the table and column names.
     *
     * @param string $name the name of the foreign key constraint.
     * @param string $table the table that the foreign key constraint will be added to.
     * @param array|string $columns the name of the column to that the constraint will be added on. If there are
     * multiple columns, separate them with commas or use an array.
     * @param string $refTable the table that the foreign key references to.
     * @param array|string $refColumns the name of the column that the foreign key references to. If there are multiple
     * columns, separate them with commas or use an array.
     * @param string|null $delete the ON DELETE option. Most DBMS support these options: RESTRICT, CASCADE, NO ACTION,
     * SET DEFAULT, SET NULL.
     * @param string|null $update the ON UPDATE option. Most DBMS support these options: RESTRICT, CASCADE, NO ACTION,
     * SET DEFAULT, SET NULL.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function addForeignKey(
        string $name,
        string $table,
        $columns,
        string $refTable,
        $refColumns,
        ?string $delete = null,
        ?string $update = null
    ): void {
        $time = $this->beginCommand(
            "Add foreign key $name: $table (" . implode(
                ',',
                (array)$columns
            ) . ") references $refTable (" . implode(
                ',',
                (array)$refColumns
            ) . ')'
        );
        $this->db->createCommand()->addForeignKey(
            $name,
            $table,
            $columns,
            $refTable,
            $refColumns,
            $delete,
            $update
        )->execute();
        $this->endCommand($time);
    }

    /**
     * Builds a SQL statement for dropping a foreign key constraint.
     *
     * @param string $name the name of the foreign key constraint to be dropped. The name will be properly quoted by the
     * method.
     * @param string $table the table whose foreign is to be dropped. The name will be properly quoted by the method.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function dropForeignKey(string $name, string $table): void
    {
        $time = $this->beginCommand("Drop foreign key $name from table $table");
        $this->db->createCommand()->dropForeignKey($name, $table)->execute();
        $this->endCommand($time);
    }

    /**
     * Builds and executes a SQL statement for creating a new index.
     *
     * @param string $name the name of the index. The name will be properly quoted by the method.
     * @param string $table the table that the new index will be created for. The table name will be properly quoted by
     * the method.
     * @param array|string $columns the column(s) that should be included in the index. If there are multiple columns,
     * please separate them by commas or use an array. Each column name will be properly quoted by the method. Quoting
     * will be skipped for column names that include a left parenthesis "(".
     * @param bool $unique whether to add UNIQUE constraint on the created index.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function createIndex(string $name, string $table, $columns, bool $unique = false): void
    {
        $time = $this->beginCommand(
            'Create' . ($unique ? ' unique' : '') . " index $name on $table (" . implode(',', (array)$columns) . ')'
        );
        $this->db->createCommand()->createIndex($name, $table, $columns, $unique)->execute();
        $this->endCommand($time);
    }

    /**
     * Builds and executes a SQL statement for dropping an index.
     *
     * @param string $name the name of the index to be dropped. The name will be properly quoted by the method.
     * @param string $table the table whose index is to be dropped. The name will be properly quoted by the method.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function dropIndex(string $name, string $table): void
    {
        $time = $this->beginCommand("Drop index $name on $table");
        $this->db->createCommand()->dropIndex($name, $table)->execute();
        $this->endCommand($time);
    }

    /**
     * Builds and execute a SQL statement for adding comment to column.
     *
     * @param string $table the table whose column is to be commented. The table name will be properly quoted by the
     * method.
     * @param string $column the name of the column to be commented. The column name will be properly quoted by the
     * method.
     * @param string $comment the text of the comment to be added. The comment will be properly quoted by the method.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function addCommentOnColumn(string $table, string $column, string $comment): void
    {
        $time = $this->beginCommand("Add comment on column $column");
        $this->db->createCommand()->addCommentOnColumn($table, $column, $comment)->execute();
        $this->endCommand($time);
    }

    /**
     * Builds a SQL statement for adding comment to table.
     *
     * @param string $table the table to be commented. The table name will be properly quoted by the method.
     * @param string $comment the text of the comment to be added. The comment will be properly quoted by the method.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function addCommentOnTable(string $table, string $comment): void
    {
        $time = $this->beginCommand("Add comment on table $table");
        $this->db->createCommand()->addCommentOnTable($table, $comment)->execute();
        $this->endCommand($time);
    }

    /**
     * Builds and execute a SQL statement for dropping comment from column.
     *
     * @param string $table the table whose column is to be commented. The table name will be properly quoted by the
     * method.
     * @param string $column the name of the column to be commented. The column name will be properly quoted by the
     * method.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function dropCommentFromColumn(string $table, string $column): void
    {
        $time = $this->beginCommand("Drop comment from column $column");
        $this->db->createCommand()->dropCommentFromColumn($table, $column)->execute();
        $this->endCommand($time);
    }

    /**
     * Builds a SQL statement for dropping comment from table.
     *
     * @param string $table the table whose column is to be commented. The table name will be properly quoted by the
     * method.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function dropCommentFromTable(string $table): void
    {
        $time = $this->beginCommand("drop comment from table $table");
        $this->db->createCommand()->dropCommentFromTable($table)->execute();
        $this->endCommand($time);
    }

    /**
     * Prepares for a command to be executed, and outputs to the console.
     *
     * @param string $description the description for the command, to be output to the console.
     *
     * @return float the time before the command is executed, for the time elapsed to be calculated.
     */
    protected function beginCommand(string $description): float
    {
        if (!$this->compact) {
            echo "    > $description ...";
        }
        return microtime(true);
    }

    /**
     * Finalizes after the command has been executed, and outputs to the console the time elapsed.
     *
     * @param float $time the time before the command was executed.
     */
    protected function endCommand(float $time): void
    {
        if (!$this->compact) {
            echo ' Done in ' . sprintf('%.3f', microtime(true) - $time) . "s.\n";
        }
    }
}
