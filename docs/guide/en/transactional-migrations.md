# Transactional migrations

A migration implementing `Yiisoft\Db\Migration\TransactionalMigrationInterface` runs in a transaction. Applying the
migration and recording it in history share a transaction. Reverting the migration and removing its history record also
share a transaction. Each migration has its own transaction.

To generate a transactional migration, use `--transaction`:

```shell
./yii migrate:create update_posts --transaction
```

To generate a migration without a transaction, use `--no-transaction`:

```shell
./yii migrate:create post --command=table --no-transaction
```

These options work with all generator commands: `create`, `table`, `dropTable`, `addColumn`, `dropColumn`, and `junction`.
Use `php bin/console` for Symfony or `./vendor/bin/yii-db-migration` for the standalone tool instead of `./yii`.

## Default transaction mode

The nullable `newMigrationTransactional` setting controls the mode for newly generated migrations:

| Value | Behavior |
| --- | --- |
| `true` | Generate transactional migrations. |
| `false` | Generate migrations without transactions. |
| `null` | Keep each template's default. |

With `null`, the built-in `table` and `junction` templates generate transactional migrations. The `create`, `dropTable`,
`addColumn`, and `dropColumn` templates generate migrations without transactions. This preserves the existing behavior.

An explicit CLI option overrides the configured mode. If neither specifies a mode, the template chooses its default.

For Yii Console, configure the default database in `config/console/params.php`:

```php
'yiisoft/db-migration' => [
    // ...
    'newMigrationTransactional' => true,
],
```

For the standalone tool, set `'newMigrationTransactional' => true` in the root `yii-db-migration.php` configuration file.

For Symfony, configure the `CreateService` constructor argument in `config/services.yml`:

```yaml
services:
    Yiisoft\Db\Migration\Service\Generate\CreateService:
        arguments:
            $transactional: true
```

When constructing the service directly, use `new CreateService($db, transactional: true)`. A call to
`CreateService::run()` can override this mode with its `transactional` argument without changing subsequent calls.

## Named databases

Each additional database has its own `newMigrationTransactional` setting. It does not inherit the default database's
setting. For Yii Console, add the setting to the corresponding entry in `databases`. For standalone or Symfony usage,
pass it as a `DatabaseSet` constructor argument:

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

See [Multiple databases](multiple-databases.md) for connection configuration.

## Existing migrations and custom templates

Generation settings do not change existing migrations. Add or remove `TransactionalMigrationInterface` in an existing
migration's implemented interfaces to change its transaction mode. Keep `RevertibleMigrationInterface` if the migration
supports `down()`.

Custom templates registered through `CreateService::setTemplate()` or `setTemplates()` receive a nullable `$transactional`
variable. It contains the per-call override, otherwise the configured mode. If both are `null`, the template can choose
its own default, for example with `$transactional ??= false`. To honor the setting, conditionally emit the import and
implementation of `TransactionalMigrationInterface`. Existing custom templates that ignore this variable keep their
previous behavior.

Transactions remain subject to the database's restrictions. Some schema operations implicitly commit or cannot run in a
transaction; adding the interface cannot make these operations rollbackable.
