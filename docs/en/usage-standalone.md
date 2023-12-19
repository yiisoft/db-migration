# Standalone usage

1. Copy configuration file `./vendor/yiisoft/db-migration/bin/MigrationContainer.php` to `root` folder of your project.

```shell
cp ./vendor/yiisoft/db-migration/bin/MigrationContainer.php ./
```

2. Edit `./MigrationContainer.php` and add definitions for `Psr\SimpleCache\CacheInterface`
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
use Yiisoft\Db\Migration\Command\CreateCommand;
use Yiisoft\Db\Migration\Command\DownCommand;
use Yiisoft\Db\Migration\Command\HistoryCommand;
use Yiisoft\Db\Migration\Command\NewCommand;
use Yiisoft\Db\Migration\Command\RedoCommand;
use Yiisoft\Db\Migration\Command\UpdateCommand;
use Yiisoft\Db\Migration\Informer\MigrationInformerInterface;
use Yiisoft\Db\Migration\Informer\NullMigrationInformer;
use Yiisoft\Db\Migration\Service\MigrationService;
use Yiisoft\Db\Sqlite\Connection;
use Yiisoft\Db\Sqlite\Driver;
use Yiisoft\Definitions\ReferencesArray;

final class MigrationContainer
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
                    new Driver('sqlite:./tests/_output/runtime/yiitest.sq3'),
                ],
            ],
            MigrationService::class => [
                'class' => MigrationService::class,
                'setCreateNamespace()' => [''],
                'setCreatePath()' => [''],
                'setUpdateNamespaces()' => [['Yii\\User\\Framework\\Migration']],
                'setUpdatePaths()' => [[]],
            ],
            MigrationInformerInterface::class => ConsoleMigrationInformer::class,
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

3. Run the console command without arguments to see the list of available migration commands:

```shell
./vendor/bin/yii-db-migration
```
