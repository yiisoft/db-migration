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

Select one set with `--db`, or apply all sets with `migrate:up`:

```shell
./yii migrate:create create_places --db=maps
./yii migrate:up --db=maps
./yii migrate:up
```

See [Multiple databases](multiple-databases.md) for command behavior, execution order, and history isolation.
