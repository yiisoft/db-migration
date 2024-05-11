# Uso com Symfony

<<<<<<< Updated upstream
Requer db-migration e driver de banco de dados. Vamos usar SQLite para este exemplo:
=======
Requer [Yii DB Migration](https://github.com/yiisoft/db-migration) e driver de banco de dados. Vamos usar [Yii DB SQLite](https://github.com/yiisoft/db-sqlite) para este exemplo:
>>>>>>> Stashed changes

```shell
composer require yiisoft/db-migration
composer require yiisoft/db-sqlite
```

<<<<<<< Updated upstream
Configure migrações e conexão de banco de dados em seu `config/services.yml`:
=======
Configure migração e a conexão de banco de dados em seu `config/services.yml`:
>>>>>>> Stashed changes

```yaml
Yiisoft\Db\Migration\:
    resource: '../vendor/yiisoft/db-migration/src/'

Yiisoft\Db\Migration\Informer\MigrationInformerInterface:
    class: 'Yiisoft\Db\Migration\Informer\ConsoleMigrationInformer'

Yiisoft\Injector\Injector:
    arguments:
        - '@service_container'

Yiisoft\Db\Migration\Service\MigrationService:
    calls:
      - setNewMigrationNamespace: ['App\Migrations']
      - setNewMigrationPath: ['']
      - setSourceNamespaces: [['App\Migrations']]
      - setSourcePaths: [[]]
        
Yiisoft\Db\:
  resource: '../vendor/yiisoft/db/src/'
  exclude:
    - '../vendor/yiisoft/db/src/Debug/'

cache.app.simple:
  class: 'Symfony\Component\Cache\Psr16Cache'
  arguments:
    - '@cache.app'

Yiisoft\Db\Cache\SchemaCache:
  arguments:
    - '@cache.app.simple'

Yiisoft\Db\Connection\ConnectionInterface:
  class: '\Yiisoft\Db\Sqlite\Connection'
  arguments:
    - '@sqlite_driver'

sqlite_driver:
  class: '\Yiisoft\Db\Sqlite\Driver'
  arguments:
    - 'sqlite:./var/migrations.sq3'
```

É isso. Agora você pode usar os comandos `bin/console migrate:*`.