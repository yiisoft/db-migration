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

The setup above supplies the `default` database unless you explicitly configure a `default` set in the registry.
The example below uses existing connection services and gives each database its own migration directory.
Merge these definitions into the same `services` section as the setup above.

Keep `DatabaseSetRegistry` excluded from resource discovery and define it explicitly as shown below. Otherwise, a later
resource registration can overwrite your configured registry with an empty one. `DatabaseSet` and `DatabaseContext` are
also excluded because they are configuration and runtime objects, rather than services to discover automatically:

```yaml
# config/services.yaml
services:
    app.migrations.default:
        class: Yiisoft\Db\Migration\DatabaseSet
        arguments:
            $db: '@yii3.connections.default'
            $newMigrationPath: '%kernel.project_dir%/config/migrations/default'

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
                default: '@app.migrations.default'
                maps: '@app.migrations.maps'
                analytics: '@app.migrations.analytics'
```

Omit `app.migrations.default` and its registry entry to keep using the original connection and migration settings.
An explicit `default` set can use a different connection, directory, and history table. It still runs first.

The registry uses the configured `Injector` to instantiate migrations, so constructor dependency injection continues to
work for migrations in additional sets.

Select one set with `--db`, or apply all sets with `migrate:up`:

```shell
php bin/console migrate:create create_places --db=maps
php bin/console migrate:up --db=maps
php bin/console migrate:up
```

See [Multiple databases](multiple-databases.md) for command behavior, execution order, and history isolation.
