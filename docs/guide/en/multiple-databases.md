# Multiple databases

If your application uses separate databases such as `maps` and `analytics`, you can organize their migrations
into isolated sets. Each set has its own migration directory and a separate migration history table in its database,
so migrations applied to one set do not affect the recorded status of migrations in another. Use `--db` to work with
one set, or apply migrations to all sets with a single command.

## Configure your databases

Your existing database is named `default`. Give each additional database a name, such as `maps` or `analytics`, and a
directory for its migrations. Define `default` in the same way when using named sets, with its own connection,
migration directory, and history table. Alternatively, keep your existing configuration for `default` and configure only
the additional sets. Choose one form for `default`; do not configure it in both places. Create the directories before generating migrations:

```text
config/migrations/default/
config/migrations/maps/
config/migrations/analytics/
```

Follow the configuration instructions for your application:

- [Standalone](usage-standalone.md#multiple-databases)
- [Yii Console](usage-with-yii-console.md#multiple-databases)
- [Symfony](usage-with-symfony.md#multiple-databases)

The following examples use the standalone executable.

## Create a migration

To create a migration in the `maps` directory:

```shell
./vendor/bin/yii-db-migration migrate:create create_places --db=maps
```

Edit the generated file with your migration operations as usual. When you apply it with `--db=maps`, those operations
run against the `maps` database.

Without `--db`, `migrate:create` creates the file in the `default` database's migration directory.

## Apply migrations

Apply pending migrations to `maps` only:

```shell
./vendor/bin/yii-db-migration migrate:up --db=maps
```

To apply pending migrations to all configured databases:

```shell
./vendor/bin/yii-db-migration migrate:up
```

The `default` database runs first, followed by the additional databases in configuration order. If `analytics` needs tables
or data created by `maps`, configure `maps` before `analytics`.
You confirm migrations separately for each database. Add `--force-yes` to skip these prompts during deployment.

To apply at most two migrations per database:

```shell
./vendor/bin/yii-db-migration migrate:up --limit=2
```

If a migration fails, execution stops before proceeding to later databases. Changes already applied to earlier databases
remain. Fix the failing migration and run the command again; migrations recorded as applied are skipped.

When selecting migration files with `--path` or `--namespace`, also specify `--db` if you have multiple databases:

```shell
./vendor/bin/yii-db-migration migrate:up --db=analytics --namespace='App\Migrations\Analytics'
```

This requirement also applies to `migrate:new`, so the selected files are checked against one database's history.

## Check pending migrations and history

View pending migrations or applied migrations for `maps`:

```shell
./vendor/bin/yii-db-migration migrate:new --db=maps
./vendor/bin/yii-db-migration migrate:history --db=maps
```

Omit `--db` to view all configured databases. Results are grouped by database. The command succeeds if at least one
database has migrations to show, and returns a failure exit code if all results are empty. An error stops execution even
if an earlier database had results. Use `--all` to show the complete list or `--limit=5` to show up to five migrations per
database.

History is stored in each database's own `migration` table, using its configured table prefix. If you configure multiple
names for the same physical database and want independent histories, give each a different `historyTable` value.

## Revert or redo a migration

Revert the last `maps` migration:

```shell
./vendor/bin/yii-db-migration migrate:down --db=maps
```

Revert and apply the last `maps` migration again:

```shell
./vendor/bin/yii-db-migration migrate:redo --db=maps
```

When multiple databases are configured, both commands require `--db`. Use `--db=default` to select the `default` database.
With only one database configured, you can continue to omit the option.

## Modify migration history

Use `migrate:mark` to record migrations without executing them. With multiple databases, `--db` is required:

```shell
./vendor/bin/yii-db-migration migrate:mark 'App\Migrations\Maps\M260101000002CreateIndex' --db=maps
```

See [Modifying migration history](migration-history.md) for target selection and resetting history.
