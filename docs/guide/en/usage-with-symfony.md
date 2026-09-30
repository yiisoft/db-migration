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

## Multiple databases

Keep the setup above for the default database. Configure additional sets using your
existing connection services, and inject the registry into the migration commands through autowiring:

```yaml
# config/services.yaml
services:
    app.migrations.maps:
        class: Yiisoft\Db\Migration\DatabaseSet
        arguments:
            $db: '@yii3.connections.maps'
            $newMigrationPath: '%kernel.project_dir%/config/migrations/maps'

    app.migrations.analytics:
        class: Yiisoft\Db\Migration\DatabaseSet
        arguments:
            $db: '@yii3.connections.analytics'
            $newMigrationPath: '%kernel.project_dir%/config/migrations/analytics'

    Yiisoft\Db\Migration\DatabaseSetRegistry:
        autowire: true
        arguments:
            $databases:
                maps: '@app.migrations.maps'
                analytics: '@app.migrations.analytics'
```

The registry uses the configured `Injector` to instantiate migrations, so constructor dependency injection continues to
work for migrations in additional sets.

Select one set with `--db`, or apply all sets with `migrate:up`:

```shell
php bin/console migrate:create create_places --db=maps
php bin/console migrate:up --db=maps
php bin/console migrate:up
```

See [Multiple databases](multiple-databases.md) for command behavior, execution order, and history isolation.
