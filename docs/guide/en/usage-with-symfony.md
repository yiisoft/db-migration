# Usage with Symfony

Require migrations package and DB driver. Let's use SQLite for this example:

```shell
composer require yiisoft/db-migration
composer require yiisoft/db-sqlite
```

Configure migrations and database connection in your `config/services.yml`:

```yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true

    Yiisoft\Db\Migration\:
        resource: '../vendor/yiisoft/db-migration/src/'
        exclude:
          - '../vendor/yiisoft/db-migration/src/DatabaseSetRegistry.php'
          - '../vendor/yiisoft/db-migration/src/DatabaseSet.php'
          - '../vendor/yiisoft/db-migration/src/DatabaseContext.php'

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

The `default` database is configured as described above: it uses the `ConnectionInterface` service and the
`MigrationService` configuration. Add a `DatabaseSet` service for each additional database and pass them to
`DatabaseSetRegistry`. The commands receive the registry through autowiring. The example below assumes that the
`app.db.maps` and `app.db.analytics` connection services already exist.

```yaml
services:
    # ...

    app.migrations.maps:
        class: Yiisoft\Db\Migration\DatabaseSet
        arguments:
            $db: '@app.db.maps'
            $newMigrationPath: '%kernel.project_dir%/config/migrations/maps'

    app.migrations.analytics:
        class: Yiisoft\Db\Migration\DatabaseSet
        arguments:
            $db: '@app.db.analytics'
            $newMigrationPath: '%kernel.project_dir%/config/migrations/analytics'

    Yiisoft\Db\Migration\DatabaseSetRegistry:
        arguments:
            $databases:
                maps: '@app.migrations.maps'
                analytics: '@app.migrations.analytics'
```

The name `default` is reserved and can't be used in the registry.

Select one set with `--db`, or apply all sets with `migrate:up`:

```shell
php bin/console migrate:create create_places --db=maps
php bin/console migrate:up --db=maps
php bin/console migrate:up
```

See [Multiple databases](multiple-databases.md) for command behavior, execution order, and history isolation.
