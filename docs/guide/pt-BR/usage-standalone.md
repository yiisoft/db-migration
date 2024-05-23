# Standalone usage

## Com arquivo de configuração

1. Copie o arquivo de configuração `./vendor/yiisoft/db-migration/bin/yii-db-migration.php` para a pasta raiz do seu projeto:

    ```shell
    cp ./vendor/yiisoft/db-migration/bin/yii-db-migration.php ./yii-db-migration.php
    ```

2. Defina a conexão do banco de dados no arquivo de configuração (consulte a 
    [documentação do Yii DB](https://github.com/yiisoft/db/blob/master/docs/guide/en/README.md#create-connection)).
    Por exemplo, conexão MySQL:

    ```php
    'db' => new \Yiisoft\Db\Mysql\Connection(
        new \Yiisoft\Db\Mysql\Driver('mysql:host=mysql;dbname=mydb', 'user', 'q1w2e3r4'),
        new \Yiisoft\Db\Cache\SchemaCache(new \Yiisoft\Cache\ArrayCache()),
    ),
    ```

3. Opcionalmente, modifique outras opções no arquivo de configuração. Cada opção possui um comentário com descrição.

4. Execute o comando do console sem argumentos para ver a lista de comandos de migração disponíveis:

    ```shell
    ./vendor/bin/yii-db-migration
    ```

## Sem arquivo de configuração

Isso pode ser útil em ambientes de teste e/ou quando vários RDBMS são usados.

Configure todas as dependências manualmente:

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

Em seguida, inicialize o comando para usar sem CLI. Por exemplo, para aplicar migrações será `UpdateCommand`:

```php
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Yiisoft\Db\Migration\Command\UpdateCommand;
use Yiisoft\Db\Migration\Runner\UpdateRunner;

$command = new UpdateCommand(new UpdateRunner($migrator), $migrationService, $migrator);
$command->setHelperSet(new HelperSet(['queestion' => new QuestionHelper()]));
```

E, por fim, execute o comando:

```php
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

$input = new ArrayInput([]);
$input->setInteractive(false);

$this->getMigrateUpdateCommand()->run($input, new NullOutput());
```
