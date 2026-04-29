# Migration types

The package provides several interfaces to implement migrations. Choosing the right interface depends on whether you
need the migration to be revertible and/or wrapped in a transaction.

## `MigrationInterface`

The base interface for all migrations. It requires implementing only the `up()` method, which contains the logic for
applying the migration.

```php
use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\MigrationInterface;

final class M250101120000CreatePostTable implements MigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $columnBuilder = $b->columnBuilder();

        $b->createTable('post', [
            'id' => $columnBuilder::primaryKey(),
            'title' => $columnBuilder::string(255)->notNull(),
        ]);
    }
}
```

Use this interface when the migration does not need to be reverted.

## `RevertibleMigrationInterface`

Extends `MigrationInterface` and adds the `down()` method for reverting the migration. This is the most commonly used
interface.

```php
use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

final class M250101120000CreatePostTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $columnBuilder = $b->columnBuilder();

        $b->createTable('post', [
            'id' => $columnBuilder::primaryKey(),
            'title' => $columnBuilder::string(255)->notNull(),
        ]);
    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropTable('post');
    }
}
```

Use this interface when you want to be able to revert the migration using `migrate:down` or `migrate:redo`.

## `TransactionalMigrationInterface`

Extends `MigrationInterface` and causes the migration to be wrapped in a database transaction. If any step of the
migration fails, all changes made by the migration are rolled back.

```php
use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;
use Yiisoft\Db\Migration\TransactionalMigrationInterface;

final class M250101120000CreatePostTable implements RevertibleMigrationInterface, TransactionalMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $columnBuilder = $b->columnBuilder();

        $b->createTable('post', [
            'id' => $columnBuilder::primaryKey(),
            'title' => $columnBuilder::string(255)->notNull(),
        ]);
    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropTable('post');
    }
}
```

Implement this interface (optionally together with `RevertibleMigrationInterface`) to run the migration inside
a transaction. Note that not all DBMS support transactional DDL. For example, MySQL implicitly commits DDL
statements and they cannot be rolled back.

## Comparison

| Interface | `up()` | `down()` | Transactional |
|-----------|--------|----------|---------------|
| `MigrationInterface` | ✓ | ✗ | ✗ |
| `RevertibleMigrationInterface` | ✓ | ✓ | ✗ |
| `MigrationInterface` + `TransactionalMigrationInterface` | ✓ | ✗ | ✓ |
| `RevertibleMigrationInterface` + `TransactionalMigrationInterface` | ✓ | ✓ | ✓ |
