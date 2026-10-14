# Transactional migrations

A migration that implements `Yiisoft\Db\Migration\TransactionalMigrationInterface` runs in a transaction.
The migrator uses a separate transaction for each migration's `up()` or `down()` method, including the history update.

Some database operations commit automatically or cannot run in a transaction.
The interface does not change these limits.

## Create a migration

To generate a transactional migration, use `--transaction`:

```shell
./yii migrate:create update_posts --transaction
```

To generate a migration without a transaction, use `--no-transaction`:

```shell
./yii migrate:create post --command=table --no-transaction
```

Both options work with all generator commands:
`create`, `table`, `dropTable`, `addColumn`, `dropColumn`, and `junction`.
For Symfony, replace `./yii` with `php bin/console`. For the standalone tool, use `./vendor/bin/yii-db-migration`.

## Default transaction mode

Set `newMigrationTransactional` to choose the default mode for new migrations:

| Value | Behavior |
| --- | --- |
| `true` | Generate transactional migrations. |
| `false` | Generate migrations without transactions. |
| `null` | Use the template's default. |

By default, the built-in `table` and `junction` templates generate transactional migrations.
The other built-in templates generate migrations without transactions.

CLI options override `newMigrationTransactional`.
If no option is set and the setting is `null`, the generator uses the template's default.

For Yii Console, set the mode for the default database in `config/console/params.php`:

```php
'yiisoft/db-migration' => [
    // ...
    'newMigrationTransactional' => true,
],
```

For the standalone tool, set `'newMigrationTransactional' => true` in the project's root `yii-db-migration.php` file.

For Symfony, configure the `CreateService` constructor argument in `config/services.yml`:

```yaml
services:
    Yiisoft\Db\Migration\Service\Generate\CreateService:
        arguments:
            $transactional: true
```

For direct PHP use, pass `transactional: true` to the `CreateService` constructor.
Pass the `transactional` argument to `CreateService::run()` to override the mode for one call.

## Named databases

Each named database has its own `newMigrationTransactional` setting. It does not inherit the default database's setting.

For Yii Console, add the setting to the database's entry in `databases`.
For standalone or Symfony usage, pass it to the `DatabaseSet` constructor:

```php
new DatabaseSet(
    $mapsConnection,
    newMigrationPath: __DIR__ . '/migrations/maps',
    newMigrationTransactional: true,
);
```

The CLI option overrides the setting for the selected database:

```shell
./yii migrate:create update_places --db=maps --no-transaction
```

See [Multiple databases](multiple-databases.md) to configure the connections.

## Change an existing migration

Generation settings do not change existing migrations.

To enable transactions, add `TransactionalMigrationInterface` to the migration's `implements` list.
To disable transactions, remove it. Keep `RevertibleMigrationInterface` if the migration supports `down()`.

## Custom templates

Templates set with `CreateService::setTemplate()` or `CreateService::setTemplates()` receive `$transactional`.
This variable contains the mode for the current call, or the configured mode if no override is set.
If the value is `null`, choose a template default, for example with `$transactional ??= false`.

Include the import and implementation of `TransactionalMigrationInterface` only when `$transactional` is `true`.
Templates that ignore this variable keep their existing behavior.
