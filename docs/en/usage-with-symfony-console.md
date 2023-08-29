# Symfony Console

1. Edit `./DiConsoleSymfony.php` and add definitions for `Psr\SimpleCache\CacheInterface`
   and `Yiisoft\Db\Connection\ConnectionInterface`.

Also, configure `MigrationService::class` and `MigrationInformerInterface::class`.

Here's a sample configuration.

```php
<?php

declare(strict_types=1);

use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Console\Application;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Sqlite\Connection;
use Yiisoft\Db\Sqlite\Driver;
use Yiisoft\Definitions\ReferencesArray;
use Yiisoft\Yii\Db\Migration\Informer\MigrationInformerInterface;
use Yiisoft\Yii\Db\Migration\Informer\NullMigrationInformer;
use Yiisoft\Yii\Db\Migration\Service\MigrationService;
use Yiisoft\Yii\Db\Migration\Command\CreateCommand;
use Yiisoft\Yii\Db\Migration\Command\DownCommand;
use Yiisoft\Yii\Db\Migration\Command\HistoryCommand;
use Yiisoft\Yii\Db\Migration\Command\NewCommand;
use Yiisoft\Yii\Db\Migration\Command\RedoCommand;
use Yiisoft\Yii\Db\Migration\Command\UpdateCommand;

final class Di
{
    public static function definitions(): array
    {
        return [
            Application::class => [
                '__construct()' => [
                    'name' => 'Yii Database Migration Tool',
                    'version' => '1.0.0',
                ],
                'addCommands()' => [self::getCommands()],
            ],
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
        return ReferencesArray::from(
            [
                CreateCommand::class,
                DownCommand::class,
                HistoryCommand::class,
                NewCommand::class,
                RedoCommand::class,
                UpdateCommand::class,
            ]
        );
    }
}
```

2. Run the command `./vendor/bin/migration` to see the list of available migration commands.

```shell
./vendor/bin/migration
```
