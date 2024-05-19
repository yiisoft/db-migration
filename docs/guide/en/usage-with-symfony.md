# Usage with Symfony

Require migrations package and DB driver. Let's use SQLite for this example:

```shell
composer require yiisoft/db-migration
composer require yiisoft/db-sqlite
```

Configure migrations and database connection in your `config/services.yml`:

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

That's it. Now you can use `bin/console migrate:*` commands.
