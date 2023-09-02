# Yii Console

In this example we use [yiisoft/app](https://github.com/yiisoft/app).

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

Add to `config/params.php`:

```php
...
'yiisoft/db-migration' => [
    'createNamespace' => 'App\\Migration',
    'updateNamespaces' => ['App\\Migration'],
],
...
```

Now the `MigrationService::class` uses the `View` of the application that is already registered in `yiisoft/view`.

Execute `composer du` in console to rebuild the configuration.

Now we have the `yiisoft/db-migration` package configured and it can be called in the console.

View the list of available commands with `./yii list`:

```shell
./yii list
```
