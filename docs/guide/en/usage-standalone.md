# Standalone usage

## With configuration file

1. Copy configuration file `./vendor/yiisoft/db-migration/bin/yii-db-migration.php` to root folder of your project:

    ```shell
    cp ./vendor/yiisoft/db-migration/bin/yii-db-migration.php ./yii-db-migration.php
    ```

2. Define DB connection in configuration file (see
   [Yii DB documentation](https://github.com/yiisoft/db/blob/master/docs/en/README.md#create-connection)).
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
$migrationService->setSourcePaths([dirname(__DIR__, 2), 'migrations']);
```

Then initialize the command for using without CLI. For example, for applying migrations it will be `UpdateCommand`:

```php
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Yiisoft\Db\Migration\Command\UpdateCommand;
use Yiisoft\Db\Migration\Runner\UpdateRunner;

$command = new UpdateCommand(new UpdateRunner($migrator), $migrationService, $migrator);
$command->setHelperSet(new HelperSet(['queestion' => new QuestionHelper()]));
```

And, finally, run the command:

```php
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

$input = new ArrayInput([]);
$input->setInteractive(false);

$this->getMigrateUpdateCommand()->run($input, new NullOutput());
```
