# Multiple databases

A database set groups a connection, migration sources, a destination for new migrations, and history-table settings.
Use this when different parts of your application, such as Maps and Analytics, have their own databases and migrations.

Your existing connection and migration configuration form the `default` set. Add other sets under names such as `maps`
and `analytics`; the name `default` is reserved. Each set may use a different database driver.

## Commands

Select one database with `--db`:

```shell
./yii migrate:create create_places --db=maps
./yii migrate:up --db=maps
./yii migrate:new --db=maps
./yii migrate:history --db=maps
./yii migrate:down --db=maps
./yii migrate:redo --db=maps
```

Replace `./yii` with `php bin/console` for Symfony or `./vendor/bin/yii-db-migration` for standalone use.

Without `--db`, commands behave as follows:

| Command | Behavior |
| --- | --- |
| `migrate:up` | Apply migrations to all sets, default first, then additional sets in configuration order. |
| `migrate:new` | Show pending migrations for all sets in the same order. |
| `migrate:history` | Show history for all sets in the same order. |
| `migrate:create` | Create a migration in the default set. |
| `migrate:down`, `migrate:redo` | Require `--db` when additional sets are configured. With only the default set, behave as before. |

Output identifies each database when multiple sets are configured. Unknown names are rejected before executing any
migration and the error lists the available names. `--db=default` explicitly selects the existing configuration.

`--limit` applies separately to each selected set. Existing `--path` and `--namespace` options also apply separately to
selected sets; they do not choose a database. Use `--db` to scope those options to one set.

Execution stops at the first error or nonzero command result. Empty results in `new` and `history` are successful when
listing all sets, so an empty database does not hide results from later ones. Selecting one empty set retains the existing
failure exit code. Each set retains the existing confirmation behavior; `migrate:up --force-yes` skips all confirmations.

There is no transaction spanning databases. If Maps fails after the default database succeeds, the default database keeps
its changes and history. Later sets are skipped. Correct the problem and rerun the command; applied migrations are skipped.
Order additional sets so that prerequisites run first. All default migrations run before any additional set.

## History and migration classes

Each set stores history using its own connection, in `{{%migration}}` by default. Separate databases may use the same table
name. If sets share a database, configure distinct `historyTable` names to keep their histories independent.

Migration classes remain ordinary implementations of `MigrationInterface`, `RevertibleMigrationInterface`, or
`TransactionalMigrationInterface`. The builder supplied to `up()` and `down()` uses the selected set's connection.
Transactions and history use that same connection. No connection property or special base class is needed.

Use separate directories and unique class names or namespaces for separate sets. For example:

```text
config/migrations/default/
config/migrations/maps/
config/migrations/analytics/
```

A `DatabaseSet` accepts the existing `newMigrationNamespace`, `newMigrationPath`, `sourceNamespaces`, and `sourcePaths`
options. Choose either a new migration namespace or a new migration path, as with single-database configuration. A
namespace must be resolvable through Composer's PSR-4 configuration, and destination directories must exist. The new
migration destination is also included when discovering migrations. Additional source paths/namespaces can contain shared
package migrations.

Each set also accepts `historyTable`, `migrationNameLimit`, `useTablePrefix`, and `maxSqlOutputLength`. These use the same
defaults as the standalone configuration; settings from the default set are not inherited by additional sets.

## Yii Console

Keep your current default configuration. Add `DatabaseSet` instances to the `databases` parameter:

```php
use Yiisoft\Db\Migration\DatabaseSet;

// $mapsConnection and $analyticsConnection implement ConnectionInterface.
return [
    'yiisoft/db-migration' => [
        'newMigrationPath' => __DIR__ . '/migrations/default',
        'databases' => [
            'maps' => new DatabaseSet(
                $mapsConnection,
                newMigrationPath: __DIR__ . '/migrations/maps',
            ),
            'analytics' => new DatabaseSet(
                $analyticsConnection,
                newMigrationPath: __DIR__ . '/migrations/analytics',
            ),
        ],
    ],
];
```

If connections are defined as container services, configure `DatabaseSetRegistry` in your console DI configuration instead:

```php
use Yiisoft\Db\Migration\DatabaseSet;
use Yiisoft\Db\Migration\DatabaseSetRegistry;
use Yiisoft\Db\Migration\Informer\MigrationInformerInterface;
use Yiisoft\Injector\Injector;
use Psr\Container\ContainerInterface;

return [
    DatabaseSetRegistry::class => static fn (
        ContainerInterface $container,
        Injector $injector,
        MigrationInformerInterface $informer,
    ) => new DatabaseSetRegistry($injector, $informer, [
        'maps' => new DatabaseSet(
            $container->get('db.maps'),
            newMigrationNamespace: 'App\\Migrations\\Maps',
        ),
    ]),
];
```

## Symfony

Keep the [standard Symfony setup](usage-with-symfony.md) for the default database. Configure additional sets using your
existing connection services, and inject the registry into the migration commands through autowiring:

```yaml
# config/services.yaml
services:
    app.migrations.maps:
        class: Yiisoft\Db\Migration\DatabaseSet
        arguments:
            $db: '@yii3.connections.maps'
            $newMigrationPath: '%kernel.project_dir%/config/migrations/maps'

    app.migrations.analytics:
        class: Yiisoft\Db\Migration\DatabaseSet
        arguments:
            $db: '@yii3.connections.analytics'
            $newMigrationPath: '%kernel.project_dir%/config/migrations/analytics'

    Yiisoft\Db\Migration\DatabaseSetRegistry:
        autowire: true
        arguments:
            $databases:
                maps: '@app.migrations.maps'
                analytics: '@app.migrations.analytics'
```

The registry uses the configured `Injector` to instantiate migrations, so constructor dependency injection continues to
work for migrations in additional sets.

## Standalone

Add `databases` to your existing `yii-db-migration.php` configuration. Keep the existing `db` and other options for the
default set:

```php
use Yiisoft\Db\Migration\DatabaseSet;

return [
    // ... existing options, including the default 'db' connection ...
    'databases' => [
        'maps' => new DatabaseSet(
            $mapsConnection,
            newMigrationPath: __DIR__ . '/config/migrations/maps',
        ),
    ],
];
```

For manual command construction, create a `DatabaseSetRegistry` with your injector, informer, and additional sets, then
pass it as the optional final `$databases` constructor argument of each command. Existing constructor calls without a
registry continue to work with the default database only.
