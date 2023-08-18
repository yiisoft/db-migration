# Getting started

Yii Db Migration is package for YiiFramework 3.0, which allows you to use migrations in your project, using Yii Db.

Supports the following databases out of the box:

- [MSSQL](https://www.microsoft.com/en-us/sql-server/sql-server-2019) of versions **2017, 2019, 2022**.
- [MySQL](https://www.mysql.com/) of versions **5.7 - 8.0**.
- [MariaDB](https://mariadb.org/) of versions **10.4 - 10.9**.
- [Oracle](https://www.oracle.com/database/) of versions **12c - 21c**.
- [PostgreSQL](https://www.postgresql.org/) of versions **9.6 - 15**. 
- [SQLite](https://www.sqlite.org/index.html) of version **3.3 and above**.

## Requirements

- PHP 8.0 or higher.
- `Filter` PHP extension.

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```shell
composer require --prefer-dist yiisoft/db-migration
```

or add

```json
"yiisoft/db-migration": "^1.0"
```

to the require section of your composer.json.

## Usage

### Yii Console

Example using [yiisoft/app](https://github.com/yiisoft/app).

Di-Container:

Create `config/common/db.php` with content:
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
'yiisoft/yii-db-migration' => [
    'createNamespace' => 'App\\Migration',
    'updateNamespaces' => ['App\\Migration'],
],
...
```

Now the `MigrationService::class` uses the `View` of the application that is already registered in `yiisoft/view`.

Execute `composer du` in console config its rebuild.

Now we have the `yiisoft/yii-db-migration` package configured and it can be called in the console.

View the list of available commands execute in console: `./yii list`

### Without Yii Console

Edit `./bin/Di.php` and add definitions for `Psr\SimpleCache\CacheInterface` and `Yiisoft\Db\Connection\ConnectionInterface`.

Also, configure `MigrationService::class` and `MigrationInformerInterface::class`. Here's a sample configuration.

```php
<?php

declare(strict_types=1);

use Yiisoft\Yii\Db\Migration\Informer\MigrationInformerInterface;
use Yiisoft\Yii\Db\Migration\Informer\NullMigrationInformer;
use Yiisoft\Yii\Db\Migration\Service\MigrationService;
use Yiisoft\Yii\Db\Migration\Command\CreateCommand;
use Yiisoft\Yii\Db\Migration\Command\DownCommand;
use Yiisoft\Yii\Db\Migration\Command\HistoryCommand;
use Yiisoft\Yii\Db\Migration\Command\NewCommand;
use Yiisoft\Yii\Db\Migration\Command\RedoCommand;
use Yiisoft\Yii\Db\Migration\Command\UpdateCommand;
use Psr\SimpleCache\CacheInterface;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Sqlite\Connection;
use Yiisoft\Db\Sqlite\Driver;

final class Di
{
    public static function definitions(): array
    {
        return [
            CacheInterface::class => ArrayCache::class,
            ConnectionInterface::class => [
                'class' => Connection::class,
                '__construct()' => [
                    new Driver('sqlite:' . './tests/_output/runtime/yiitest.sq3'),
                ],
            ],
            MigrationService::class => [
                'class' => MigrationService::class,
                'createNamespace()' => [''],
                'createPath()' => [''],
                'updateNamespaces()' => [['Yii\\User\\Framework\\Migration']],
                'updatePaths()' => [[]],
            ],
            MigrationInformerInterface::class => NullMigrationInformer::class,
        ];
    }

    public static function getCommands(): array
    {
        return [
            CreateCommand::class,
            DownCommand::class,
            HistoryCommand::class,
            NewCommand::class,
            RedoCommand::class,
            UpdateCommand::class,
        ];
    }
}
```

> Note: The script `.bin/yii` its copy to `vendor/bin/yii`, also you can add more command console in `getCommands()`.

```
Available commands:
  migrate:create  Generate migration template.
  help             Displays help for a command
  list             Lists commands
  migrate:down     Downgrades the application by reverting old migrations.
  migrate:history  Displays the migration history.
  migrate:new      Displays the first 10 new migrations.
  migrate:redo     Redoes the last few migrations.
  migrate:up       Upgrades the application by applying new migrations.
  serve            Runs PHP built-in web server
```

Help simple command execute in console `./yii migrate:create --help`.

```
Description:
  Generate migration template.

Usage:
  migrate:create [options] [--] <name>

Arguments:
  name                         Table name for generate migration.

Options:
  -c, --command[=COMMAND]      Command to execute. [default: "create"]
  -f, --fields[=FIELDS]        To create table fields right away
      --and[=AND]              And junction
      --namespace[=NAMESPACE]  Namespace migration
  -h, --help                   Display this help message
  -q, --quiet                  Do not output any message
  -V, --version                Display this application version
      --ansi                   Force ANSI output
      --no-ansi                Disable ANSI output
  -n, --no-interaction         Do not ask any interactive question
  -v|vv|vvv, --verbose         Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
  This command Generate migration template
```
