# Upgrading Instructions for Yii DB Migration

This file contains the upgrade notes. These notes highlight changes that could break your
application when you upgrade the package from one version to another.

> **Important!** The following upgrading instructions are cumulative. That is, if you want
> to upgrade from version A to version C and there is version B between A and C, you need
> to following the instructions for both A and B.

## Upgrade from 1.x to 2.x

Use `\Yiisoft\Db\Schema\Column\ColumnBuilder` to create table column definitions.

```php
use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Schema\Column\ColumnBuilder;

/** @var MigrationBuilder $b */
$b->createTable('user', [
    'id' => ColumnBuilder::primaryKey(),
    'name' => ColumnBuilder::string(64)->notNull(),
    'age' => ColumnBuilder::integer(),
    'created_at' => ColumnBuilder::timestamp(),
]);
```
