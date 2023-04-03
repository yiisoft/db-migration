<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px">
    </a>
    <h1 align="center">Yii DB Migration</h1>
    <br>
</p>

The package implementing migration for [yiisoft/db](https://github.com/yiisoft/db).

[![Latest Stable Version](https://poser.pugx.org/yiisoft/yii-db-migration/v/stable.png)](https://packagist.org/packages/yiisoft/yii-db-migration)
[![Total Downloads](https://poser.pugx.org/yiisoft/yii-db-migration/downloads.png)](https://packagist.org/packages/yiisoft/yii-db-migration)
[![Build status](https://github.com/yiisoft/yii-db-migration/workflows/build/badge.svg)](https://github.com/yiisoft/yii-db-migration/actions?query=workflow%3Abuild)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/yiisoft/yii-db-migration/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/yii-db-migration/?branch=master) [![Code Coverage](https://scrutinizer-ci.com/g/yiisoft/yii-db-migration/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/yii-db-migration/?branch=master)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fyii-db-migration%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/yii-db-migration/master)
[![static analysis](https://github.com/yiisoft/yii-db-migration/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/yii-db-migration/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/yii-db-migration/coverage.svg)](https://shepherd.dev/github/yiisoft/yii-db-migration)

## Installation

The package could be installed via composer:

```shell
composer require yiisoft/yii-db-migration --prefer-dist
```

**Note: You must install the repository of the implementation to use.**

Example:

```shell
composer require yiisoft/db-sqlite --prefer-dist
```

## Requirements

- PHP 8.0 or higher.
- `Filter` PHP extension.

## Configuration

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
    'updateNamespace' => ['App\\Migration'],
],
...
```

Now the `MigrationService::class` uses the `View` of the application that is already registered in `yiisoft/view`.

Execute `composer du` in console config its rebuild.

Now we have the `yiisoft/yii-db-migration` package configured and it can be called in the console.

View the list of available commands execute in console: `./yii list`

```
Available commands:
  database/list    Lists all tables in the database.
  migrate/create  Generate migration template.
  help             Displays help for a command
  list             Lists commands
  migrate/down     Downgrades the application by reverting old migrations.
  migrate/history  Displays the migration history.
  migrate/new      Displays the first 10 new migrations.
  migrate/redo     Redoes the last few migrations.
  migrate/up       Upgrades the application by applying new migrations.
  serve            Runs PHP built-in web server
```

Help simple command execute in console `./yii migrate/create --help`.

```
Description:
  Generate migration template.

Usage:
  migrate/create [options] [--] <name>

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

## Testing

### Unit testing

The package is tested with [PHPUnit](https://phpunit.de/). For tests need PostgreSQL database with configuration:

- host: `127.0.0.1`
- port: `5432`
- name: `testdb`
- user: `postgres`
- password: `postgres`

#### Docker Image

To easily set up a pre-configured PostgreSQL instance for testing you can use the [docker-compose.yml](https://docs.docker.com/compose/compose-file/) 
file in this repository.

For running the docker containers you can use the following command:

```shell
docker compose up -d
```

To run tests:

```shell
./vendor/bin/phpunit
```

### Mutation testing

The package tests are checked with [Infection](https://infection.github.io/) mutation framework with
[Infection Static Analysis Plugin](https://github.com/Roave/infection-static-analysis-plugin). To run it:

```shell
./vendor/bin/roave-infection-static-analysis-plugin
```

### Static analysis

The code is statically analyzed with [Psalm](https://psalm.dev/). To run static analysis:

```shell
./vendor/bin/psalm
```

## License

The Yii Db Migration is free software. It is released under the terms of the BSD License.
Please see [`LICENSE`](./LICENSE.md) for more information.

Maintained by [Yii Software](https://www.yiiframework.com/).

## Support the project

[![Open Collective](https://img.shields.io/badge/Open%20Collective-sponsor-7eadf1?logo=open%20collective&logoColor=7eadf1&labelColor=555555)](https://opencollective.com/yiisoft)

## Follow updates

[![Official website](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat)](https://www.yiiframework.com/)
[![Twitter](https://img.shields.io/badge/twitter-follow-1DA1F2?logo=twitter&logoColor=1DA1F2&labelColor=555555?style=flat)](https://twitter.com/yiiframework)
[![Telegram](https://img.shields.io/badge/telegram-join-1DA1F2?style=flat&logo=telegram)](https://t.me/yii3en)
[![Facebook](https://img.shields.io/badge/facebook-join-1DA1F2?style=flat&logo=facebook&logoColor=ffffff)](https://www.facebook.com/groups/yiitalk)
[![Slack](https://img.shields.io/badge/slack-join-1DA1F2?style=flat&logo=slack)](https://yiiframework.com/go/slack)
