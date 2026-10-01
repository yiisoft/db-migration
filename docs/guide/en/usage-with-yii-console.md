# Usage with Yii Console

In this example, we use [yiisoft/app](https://github.com/yiisoft/app).

First, configure DI container. Create `config/common/db.php` with the following content:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Sqlite\Connection as SqliteConnection;

return [
    ConnectionInterface::class => [
        'class' => SqliteConnection::class,
        '__construct()' => [
            'dsn' => 'sqlite:' . __DIR__ . '/Data/yiitest.sq3'
        ]
    ]
];
```

Add to `config/console/params.php`:

```php
...
'yiisoft/db-migration' => [
    'newMigrationNamespace' => 'App\\Migration',
    'sourceNamespaces' => ['App\\Migration'],
],
...
```

> [!NOTE]
> `sourceNamespaces`, `sourcePaths`, `newMigrationNamespace`, and `newMigrationPath` will be used to find migrations.

Execute `composer du` in console to rebuild the configuration.

Now we have the `yiisoft/db-migration` package configured and it can be called in the console.

View the list of available commands with `./yii list`:

```shell
./yii list
```

## Multiple databases

Define your migration sets, including `default`, in the `databases` parameter:

```php
use Yiisoft\Db\Migration\DatabaseSet;

// These connection objects implement ConnectionInterface.
return [
    'yiisoft/db-migration' => [
        'databases' => [
            'default' => new DatabaseSet(
                $defaultConnection,
                newMigrationPath: __DIR__ . '/migrations/default',
            ),
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

Move existing `newMigrationNamespace`, `newMigrationPath`, `sourceNamespaces`, and `sourcePaths` parameters into the
`default` set and remove the original entries. Supplying both forms raises a configuration error. No application-wide
`ConnectionInterface` binding is required for migration commands in this form; each set supplies its connection.
Other parts of your application can continue to use their own database services.

If you keep the legacy form, omit `databases['default']`. The existing migration service configuration and connection
then supply `default`, and `databases` can contain additional sets.

For custom DI wiring, use `CommandFactory` with a registry containing `default` to build commands directly from the
sets. Replace the old migration service wiring when switching to this form; configuring those services separately
would leave unused settings. The [Symfony example](usage-with-symfony.md#multiple-databases) shows this approach.

Select one set with `--db`, or apply all sets with `migrate:up`:

```shell
./yii migrate:create create_places --db=maps
./yii migrate:up --db=maps
./yii migrate:up
```

See [Multiple databases](multiple-databases.md) for command behavior, execution order, and history isolation.
