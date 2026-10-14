# Usage with Yii Console

In this example, we use [yiisoft/app](https://github.com/yiisoft/app).

First, configure DI container. Create `config/common/db.php` with the following content:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Sqlite\Connection as SqliteConnection;
use Yiisoft\Db\Sqlite\Driver as SqliteDriver;

return [
    ConnectionInterface::class => [
        'class' => SqliteConnection::class,
        '__construct()' => [
            'driver' => new SqliteDriver('sqlite:' . __DIR__ . '/Data/yiitest.sq3'),
        ],
    ],
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

The `default` database is configured as described above: it uses the `ConnectionInterface` service and the
migration parameters. Define each additional database in the `databases` parameter, referencing its connection by
a DI container service ID:

```php
'yiisoft/db-migration' => [
    'newMigrationNamespace' => 'App\\Migration',
    'sourceNamespaces' => ['App\\Migration'],
    'databases' => [
        'maps' => [
            'db' => 'db.maps',
            'newMigrationPath' => dirname(__DIR__, 2) . '/migrations/maps',
        ],
        'analytics' => [
            'db' => 'db.analytics',
            'newMigrationPath' => dirname(__DIR__, 2) . '/migrations/analytics',
        ],
    ],
],
```

Each set is an array of `Yiisoft\Db\Migration\DatabaseSet` constructor arguments, where `db` is the ID of a connection
service. Define these connections in the DI container, for example, in `config/common/db.php`:

```php
use Yiisoft\Db\Sqlite\Connection as SqliteConnection;
use Yiisoft\Db\Sqlite\Driver as SqliteDriver;

return [
    // ...
    'db.maps' => [
        'class' => SqliteConnection::class,
        '__construct()' => [
            'driver' => new SqliteDriver('sqlite:' . dirname(__DIR__, 2) . '/runtime/maps.sq3'),
        ],
    ],
    'db.analytics' => [
        'class' => SqliteConnection::class,
        '__construct()' => [
            'driver' => new SqliteDriver('sqlite:' . dirname(__DIR__, 2) . '/runtime/analytics.sq3'),
        ],
    ],
];
```

The name `default` is reserved and can't be used in `databases`.

Select one set with `--db`, or apply all sets with `migrate:up`:

```shell
./yii migrate:create create_places --db=maps
./yii migrate:up --db=maps
./yii migrate:up
```

See [Multiple databases](multiple-databases.md) for command behavior, execution order, and history isolation.
