<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px">
    </a>
    <h1 align="center">Yii DB Migration</h1>
    <br>
</p>

Yii Db Migration is package for YiiFramework 3.0, which allows you to use migrations in your project, using Yii Db.

Supports the following databases out of the box:

- [MSSQL](https://www.microsoft.com/en-us/sql-server/sql-server-2019) of versions **2017, 2019, 2022**.
- [MySQL](https://www.mysql.com/) of versions **5.7 - 8.0**.
- [MariaDB](https://mariadb.org/) of versions **10.4 - 10.9**.
- [Oracle](https://www.oracle.com/database/) of versions **12c - 21c**.
- [PostgreSQL](https://www.postgresql.org/) of versions **9.6 - 15**. 
- [SQLite](https://www.sqlite.org/index.html) of version **3.3 and above**.

[![Latest Stable Version](https://poser.pugx.org/yiisoft/yii-db-migration/v/stable.png)](https://packagist.org/packages/yiisoft/yii-db-migration)
[![Total Downloads](https://poser.pugx.org/yiisoft/yii-db-migration/downloads.png)](https://packagist.org/packages/yiisoft/yii-db-migration)
[![Build status](https://github.com/yiisoft/yii-db-migration/workflows/build/badge.svg)](https://github.com/yiisoft/yii-db-migration/actions?query=workflow%3Abuild)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/yiisoft/yii-db-migration/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/yii-db-migration/?branch=master)
[![codecov](https://codecov.io/gh/yiisoft/yii-db-migration/branch/master/graph/badge.svg?token=CCRKELEOHP)](https://codecov.io/gh/yiisoft/yii-db-migration)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fyii-db-migration%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/yii-db-migration/master)
[![static analysis](https://github.com/yiisoft/yii-db-migration/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/yii-db-migration/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/yii-db-migration/coverage.svg)](https://shepherd.dev/github/yiisoft/yii-db-migration)

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

## Usage with Yii Console

[Check the instructions](/docs/en/usage-with-yii-console.md) to learn about usage with Yii Console.

## Usage with Symfony Console

[Check the instructions](/docs/en/usage-with-symfony-console.md) to learn about usage with Symfony Console.

## Command list

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

## Support

If you need help or have a question, the [Yii Forum](https://forum.yiiframework.com/c/yii-3-0/db/68) is a good place for that.
You may also check out other [Yii Community Resources](https://www.yiiframework.com/community).

## Testing

[Check the testing instructions](/docs/en/testing.md) to learn about testing.

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
