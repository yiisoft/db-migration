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
          - '../vendor/yiisoft/db-migration/src/Command/CommandFactory.php'

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

Use the following migration service configuration in place of the migration resource registration and
`MigrationService` configuration above. It assumes the three connection services already exist. Each connection,
migration directory, and history setting is defined in its `DatabaseSet` only; no separate default migration services
or application-wide `ConnectionInterface` binding are needed.

Do not register the migration package through a broad `resource` rule alongside these definitions. A later resource
registration can replace explicit service definitions, including the configured registry.

```yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true

    Yiisoft\Injector\Injector:
        arguments: ['@service_container']

    Yiisoft\Db\Migration\Informer\MigrationInformerInterface:
        class: Yiisoft\Db\Migration\Informer\ConsoleMigrationInformer

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
        arguments:
            $databases:
                default: '@app.migrations.default'
                maps: '@app.migrations.maps'
                analytics: '@app.migrations.analytics'

    Yiisoft\Db\Migration\Command\CommandFactory: ~

    Yiisoft\Db\Migration\Command\CreateCommand:
        factory: ['@Yiisoft\Db\Migration\Command\CommandFactory', 'create']
        arguments: ['create']

    Yiisoft\Db\Migration\Command\DownCommand:
        factory: ['@Yiisoft\Db\Migration\Command\CommandFactory', 'create']
        arguments: ['down']

    Yiisoft\Db\Migration\Command\HistoryCommand:
        factory: ['@Yiisoft\Db\Migration\Command\CommandFactory', 'create']
        arguments: ['history']

    Yiisoft\Db\Migration\Command\MarkCommand:
        factory: ['@Yiisoft\Db\Migration\Command\CommandFactory', 'create']
        arguments: ['mark']

    Yiisoft\Db\Migration\Command\NewCommand:
        factory: ['@Yiisoft\Db\Migration\Command\CommandFactory', 'create']
        arguments: ['new']

    Yiisoft\Db\Migration\Command\RedoCommand:
        factory: ['@Yiisoft\Db\Migration\Command\CommandFactory', 'create']
        arguments: ['redo']

    Yiisoft\Db\Migration\Command\UpdateCommand:
        factory: ['@Yiisoft\Db\Migration\Command\CommandFactory', 'create']
        arguments: ['up']
```

To retain your legacy default configuration instead, keep the single-database setup above, omit the `default` entry
from the registry, and use the original autowired command services instead of `CommandFactory`. The registry can still
contain additional sets. Choose one wiring style; do not keep both configurations for the default migration services.

Select one set with `--db`, or apply all sets with `migrate:up`:

```shell
php bin/console migrate:create create_places --db=maps
php bin/console migrate:up --db=maps
php bin/console migrate:up
```

See [Multiple databases](multiple-databases.md) for command behavior, execution order, and history isolation.
