# Creating migrations

## Generating migration files

The `migrate:create` command generates a new migration file in one of the configured source paths or namespaces.

### Basic migration

```shell
./yii migrate:create my_migration
```

This generates a skeleton migration class with empty `up()` and `down()` methods:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

final class M250101120000MyMigration implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        // TODO: Implement the logic to apply the migration.
    }

    public function down(MigrationBuilder $b): void
    {
        // TODO: Implement the logic to revert the migration.
    }
}
```

### Create table migration

```shell
./yii migrate:create post --command=table --fields="title:string,body:text"
```

This generates a migration class that creates a `post` table with a `title` and `body` column.

### Drop table migration

```shell
./yii migrate:create post --command=dropTable --fields="title:string,body:text"
```

### Add column migration

```shell
./yii migrate:create post --command=addColumn --fields="position:integer"
```

### Drop column migration

```shell
./yii migrate:create post --command=dropColumn --fields="position:integer"
```

### Junction table migration

```shell
./yii migrate:create post --command=junction --and=tag
```

This generates a migration for a junction table `post_tag` that relates `post` and `tag` tables.

## Field syntax

The `--fields` option accepts a comma-separated list of fields in the format:

```
name:type:decorator1:decorator2
```

For example:

```shell
./yii migrate:create post --command=table --fields="title:string(100):notNull,body:text,created_at:datetime:notNull,views:integer:notNull:defaultValue(0)"
```

### Available column types

The following column types correspond to `ColumnBuilder` static methods (the actual SQL type depends on the DBMS used):

| Type | Description |
|------|-------------|
| `primaryKey` | Auto-increment integer primary key |
| `bigPrimaryKey` | Auto-increment bigint primary key |
| `char` | Fixed-length character string |
| `string` | Variable-length character string (default 255) |
| `text` | Long text |
| `tinyint` | 1-byte integer |
| `smallint` | 2-byte integer |
| `integer` | 4-byte integer |
| `bigint` | 8-byte integer |
| `float` | Floating-point number |
| `double` | Double-precision floating-point number |
| `decimal` | Decimal number |
| `datetime` | Date and time |
| `timestamp` | Timestamp |
| `time` | Time |
| `date` | Date |
| `binary` | Binary data |
| `boolean` | Boolean value |
| `json` | JSON data |

### Available decorators

Decorators are used to set column properties. They are chained after the type.
Parentheses can be omitted for decorators without arguments (e.g. `notNull` is equivalent to `notNull()`).

| Decorator | Description |
|-----------|-------------|
| `notNull()` | Column does not allow NULL values |
| `null()` | Column allows NULL values |
| `unique()` | Column value must be unique |
| `unsigned()` | Unsigned integer type |
| `defaultValue(value)` | Default column value |
| `check(expression)` | Check constraint |
| `comment(text)` | Column comment |
| `primaryKey()` | Mark as primary key |
| `foreignKey(table column)` | Create foreign key constraint |

### Foreign keys

To add a foreign key, use the `foreignKey` decorator with the referenced table and column:

```shell
./yii migrate:create post --command=table --fields="author_id:integer:notNull:foreignKey(user id)"
```

If the column name ends with `_id`, the table name is automatically derived (e.g. `author_id` references the `author` table).

## Namespaced migrations

To generate a namespaced migration, use the `--namespace` option:

```shell
./yii migrate:create post --command=table --namespace=App\\Migrations
```

Or specify the path to the migrations directory:

```shell
./yii migrate:create post --command=table --path=@root/migrations/blog
```

## Writing migration logic

After generating a migration file, you implement the actual logic using the `MigrationBuilder` instance passed to `up()` and `down()`.

### Creating a table

```php
public function up(MigrationBuilder $b): void
{
    $columnBuilder = $b->columnBuilder();

    $b->createTable('post', [
        'id' => $columnBuilder::primaryKey(),
        'title' => $columnBuilder::string(255)->notNull(),
        'body' => $columnBuilder::text()->notNull(),
        'created_at' => $columnBuilder::datetime()->notNull(),
        'updated_at' => $columnBuilder::datetime(),
    ]);
}

public function down(MigrationBuilder $b): void
{
    $b->dropTable('post');
}
```

### Adding and dropping columns

```php
public function up(MigrationBuilder $b): void
{
    $columnBuilder = $b->columnBuilder();

    $b->addColumn('post', 'views', $columnBuilder::integer()->notNull()->defaultValue(0));
}

public function down(MigrationBuilder $b): void
{
    $b->dropColumn('post', 'views');
}
```

### Adding foreign keys

```php
public function up(MigrationBuilder $b): void
{
    $b->addForeignKey(
        'post',
        'fk-post-author_id',
        'author_id',
        'user',
        'id',
        'CASCADE',
        'CASCADE',
    );
}

public function down(MigrationBuilder $b): void
{
    $b->dropForeignKey('post', 'fk-post-author_id');
}
```

### Creating indexes

```php
public function up(MigrationBuilder $b): void
{
    $b->createIndex('post', 'idx-post-title', 'title');
    $b->createIndex('post', 'idx-post-author_id-status', ['author_id', 'status']);
}

public function down(MigrationBuilder $b): void
{
    $b->dropIndex('post', 'idx-post-author_id-status');
    $b->dropIndex('post', 'idx-post-title');
}
```

### Inserting data

```php
public function up(MigrationBuilder $b): void
{
    $b->insert('settings', ['key' => 'default_language', 'value' => 'en']);
    $b->batchInsert('settings', ['key', 'value'], [
        ['theme', 'default'],
        ['timezone', 'UTC'],
    ]);
}
```

### Executing raw SQL

```php
public function up(MigrationBuilder $b): void
{
    $b->execute('ALTER TABLE post ADD FULLTEXT INDEX idx_title (title)');
}
```

## Available `MigrationBuilder` methods

| Method | Description |
|--------|-------------|
| `execute($sql, $params)` | Executes a raw SQL statement |
| `insert($table, $columns)` | Inserts a row into a table |
| `batchInsert($table, $columns, $rows)` | Inserts multiple rows into a table |
| `upsert($table, $insertColumns, $updateColumns)` | Inserts or updates a row |
| `update($table, $columns, $condition = '', $from = null, $params = [])` | Updates rows in a table |
| `delete($table, $condition = '', $params = [])` | Deletes rows from a table |
| `createTable($table, $columns)` | Creates a new table |
| `renameTable($table, $newName)` | Renames a table |
| `dropTable($table)` | Drops a table |
| `truncateTable($table)` | Truncates a table |
| `addColumn($table, $column, $type)` | Adds a column to a table |
| `dropColumn($table, $column)` | Drops a column from a table |
| `renameColumn($table, $name, $newName)` | Renames a column |
| `alterColumn($table, $column, $type)` | Changes the definition of a column |
| `addPrimaryKey($table, $name, $columns)` | Adds a primary key constraint |
| `dropPrimaryKey($table, $name)` | Drops a primary key constraint |
| `addForeignKey($table, $name, $columns, $referenceTable, $referenceColumns, $delete, $update)` | Adds a foreign key constraint |
| `dropForeignKey($table, $name)` | Drops a foreign key constraint |
| `createIndex($table, $name, $columns, $indexType, $indexMethod)` | Creates an index |
| `dropIndex($table, $name)` | Drops an index |
| `createView($viewName, $subQuery)` | Creates a view |
| `dropView($viewName)` | Drops a view |
| `addCommentOnColumn($table, $column, $comment)` | Adds a comment on a column |
| `addCommentOnTable($table, $comment)` | Adds a comment on a table |
| `dropCommentFromColumn($table, $column)` | Drops a comment from a column |
| `dropCommentFromTable($table)` | Drops a comment from a table |
