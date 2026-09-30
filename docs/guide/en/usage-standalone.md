# Standalone usage

## With configuration file

1. Copy configuration file `./vendor/yiisoft/db-migration/bin/yii-db-migration.php` to root folder of your project:

    ```shell
    cp ./vendor/yiisoft/db-migration/bin/yii-db-migration.php ./yii-db-migration.php
    ```

2. Define DB connection in configuration file (see
   [Yii DB documentation](https://github.com/yiisoft/db/blob/master/docs/guide/en/README.md#create-connection)).
   For example, MySQL connection:

    ```php
    'db' => new \Yiisoft\Db\Mysql\Connection(
        new \Yiisoft\Db\Mysql\Driver('mysql:host=mysql;dbname=mydb', 'user', 'q1w2e3r4'),
        new \Yiisoft\Db\Cache\SchemaCache(new \Yiisoft\Cache\ArrayCache()),
    ),
    ```

3. Optionally, modify other options in the configuration file. Each option has a comment with description.

4. Run the console command without arguments to see the list of available migration commands:

    ```shell
    ./vendor/bin/yii-db-migration
    ```

## Multiple databases

Add `databases` to your existing `yii-db-migration.php`. The example below assumes you
have already created the `$mapsConnection` and `$analyticsConnection` database connections, as described in
[With configuration file](#with-configuration-file).

```php
use Yiisoft\Db\Migration\DatabaseSet;

return [
    // Keep your existing options, including 'db' for the default database.
    'newMigrationPath' => __DIR__ . '/config/migrations/default',
    'databases' => [
        'maps' => new DatabaseSet(
            $mapsConnection,
            newMigrationPath: __DIR__ . '/config/migrations/maps',
        ),
        'analytics' => new DatabaseSet(
            $analyticsConnection,
            newMigrationPath: __DIR__ . '/config/migrations/analytics',
        ),
    ],
];
```

To override the `default` database, add a `default` entry alongside `maps` and `analytics` in `databases`:

```php
'default' => new DatabaseSet(
    $defaultConnection,
    newMigrationPath: __DIR__ . '/config/migrations/default',
    historyTable: 'default_migration',
),
```

Here `$defaultConnection` is the connection you want to use for that set. Without this entry, the existing connection
and migration settings continue to supply `default`. Keep the existing base configuration in place.

Create the migration directories before generating migrations. Then select a database with `--db`:

```shell
./vendor/bin/yii-db-migration migrate:create create_places --db=maps
./vendor/bin/yii-db-migration migrate:up --db=maps
```

See [Multiple databases](multiple-databases.md) for applying migrations across databases, checking history, and reverting
changes.

## Without configuration file

This can be useful in testing environment and/or when multiple RDBMS are used.

Configure all dependencies manually:

```php
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Migration\Informer\NullMigrationInformer;
use Yiisoft\Db\Migration\Migrator;
use Yiisoft\Db\Migration\Service\MigrationService;
use Yiisoft\Injector\Injector;

/** @var ConnectionInterface $database */
$migrator = new Migrator($database, new NullMigrationInformer());
$migrationService = new MigrationService($database, new Injector(), $migrator);
$migrationService->setNewMigrationPath(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'migrations');
```

> [!NOTE]
> `sourceNamespaces`, `sourcePaths`, `newMigrationNamespace`, and `newMigrationPath` will be used to find migrations.

Then initialize the command for using without CLI. For example, for applying migrations it will be `UpdateCommand`:

```php
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Yiisoft\Db\Migration\Command\UpdateCommand;
use Yiisoft\Db\Migration\Runner\UpdateRunner;

$command = new UpdateCommand(new UpdateRunner($migrator), $migrationService, $migrator);
$command->setHelperSet(new HelperSet(['question' => new QuestionHelper()]));
```

And, finally, run the command:

```php
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

$input = new ArrayInput([]);
$input->setInteractive(false);

$this->getMigrateUpdateCommand()->run($input, new NullOutput());
```
