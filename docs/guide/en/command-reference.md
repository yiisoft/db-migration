# Command reference

## `migrate:create`

Creates a new migration file.

```shell
./yii migrate:create <name> [options]
```

### Arguments

| Argument | Description |
|----------|-------------|
| `name` | The table name or migration name to generate the migration for. |

### Options

| Option | Shortcut | Description | Default |
|--------|----------|-------------|---------|
| `--command` | `-c` | The type of migration to generate. Available values: `create`, `table`, `dropTable`, `addColumn`, `dropColumn`, `junction`. | `create` |
| `--fields` | `-f` | Comma-separated field definitions for the migration (e.g. `title:string,body:text`). | |
| `--table-comment` | | Comment to add on the table. | |
| `--and` | | The second table name for a `junction` migration. | |
| `--path` | | Path to the directory where the new migration file will be created. | |
| `--namespace` | `-ns` | Namespace of the new migration class. | |

### Examples

```shell
# Create a basic migration skeleton
./yii migrate:create my_migration

# Create a migration for a new table with fields
./yii migrate:create post --command=table --fields="title:string:notNull,body:text,created_at:datetime:notNull"

# Create a migration to drop a table
./yii migrate:create post --command=dropTable

# Create a migration to add a column
./yii migrate:create post --command=addColumn --fields="views:integer:notNull:defaultValue(0)"

# Create a migration to drop a column
./yii migrate:create post --command=dropColumn --fields="views:integer"

# Create a junction table migration
./yii migrate:create post --command=junction --and=tag

# Create a namespaced migration
./yii migrate:create post --command=table --namespace=App\\Migrations

# Create a migration in a specific directory
./yii migrate:create post --command=table --path=@root/migrations/blog
```

---

## `migrate:up`

Applies new (not yet applied) migrations.

```shell
./yii migrate:up [options]
```

### Options

| Option | Shortcut | Description | Default |
|--------|----------|-------------|---------|
| `--limit` | `-l` | The number of migrations to apply. Applies all new migrations if not specified. | |
| `--path` | | Path(s) to migration directories to apply. Can be specified multiple times. | |
| `--namespace` | `-ns` | Namespace(s) of migrations to apply. Can be specified multiple times. | |
| `--force-yes` | `-y` | Skip the confirmation prompt. | |

### Examples

```shell
# Apply all new migrations
./yii migrate:up

# Apply the first 3 new migrations
./yii migrate:up --limit=3

# Apply new migrations from a specific directory
./yii migrate:up --path=@vendor/yiisoft/rbac-db/migrations

# Apply new migrations from a specific namespace
./yii migrate:up --namespace=Yiisoft\\Rbac\\Db\\Migrations

# Apply new migrations from multiple directories
./yii migrate:up --path=@vendor/yiisoft/rbac-db/migrations --path=@vendor/yiisoft/cache-db/migrations

# Apply without confirmation prompt
./yii migrate:up --force-yes
```

---

## `migrate:down`

Reverts previously applied migrations.

```shell
./yii migrate:down [options]
```

### Options

| Option | Shortcut | Description | Default |
|--------|----------|-------------|---------|
| `--limit` | `-l` | The number of migrations to revert. | `1` |
| `--all` | `-a` | Revert all applied migrations. | |
| `--path` | | Path(s) to migration directories to revert. Can be specified multiple times. | |
| `--namespace` | `-ns` | Namespace(s) of migrations to revert. Can be specified multiple times. | |
| `--force-yes` | `-y` | Skip the confirmation prompt. | |

### Examples

```shell
# Revert the last applied migration
./yii migrate:down

# Revert the last 3 applied migrations
./yii migrate:down --limit=3

# Revert all applied migrations
./yii migrate:down --all

# Revert the last migration from a specific directory
./yii migrate:down --path=@vendor/yiisoft/rbac-db/migrations

# Revert the last migration from a specific namespace
./yii migrate:down --namespace=Yiisoft\\Rbac\\Db\\Migrations
```

---

## `migrate:redo`

Reverts and then re-applies the last migration(s).

```shell
./yii migrate:redo [options]
```

### Options

| Option | Shortcut | Description | Default |
|--------|----------|-------------|---------|
| `--limit` | `-l` | The number of migrations to redo. | `1` |
| `--all` | `-a` | Redo all applied migrations. | |
| `--path` | | Path(s) to migration directories to redo. Can be specified multiple times. | |
| `--namespace` | `-ns` | Namespace(s) of migrations to redo. Can be specified multiple times. | |
| `--force-yes` | `-y` | Skip the confirmation prompt. | |

### Examples

```shell
# Redo the last applied migration
./yii migrate:redo

# Redo the last 3 applied migrations
./yii migrate:redo --limit=3

# Redo all applied migrations
./yii migrate:redo --all

# Redo the last migration from a specific directory
./yii migrate:redo --path=@vendor/yiisoft/rbac-db/migrations
```

---

## `migrate:history`

Displays the list of applied migrations.

```shell
./yii migrate:history [options]
```

### Options

| Option | Shortcut | Description | Default |
|--------|----------|-------------|---------|
| `--limit` | `-l` | The number of migrations to display. | `10` |
| `--all` | `-a` | Display all applied migrations. | |

### Examples

```shell
# Display the last 10 applied migrations
./yii migrate:history

# Display the last 5 applied migrations
./yii migrate:history --limit=5

# Display all applied migrations
./yii migrate:history --all
```

---

## `migrate:new`

Displays the list of new (not yet applied) migrations.

```shell
./yii migrate:new [options]
```

### Options

| Option | Shortcut | Description | Default |
|--------|----------|-------------|---------|
| `--limit` | `-l` | The number of migrations to display. | `10` |
| `--all` | `-a` | Display all new migrations. | |
| `--path` | | Path(s) to migration directories to check. Can be specified multiple times. | |
| `--namespace` | `-ns` | Namespace(s) of migrations to check. Can be specified multiple times. | |

### Examples

```shell
# Display the first 10 new migrations
./yii migrate:new

# Display the first 5 new migrations
./yii migrate:new --limit=5

# Display all new migrations
./yii migrate:new --all

# Display new migrations from a specific directory
./yii migrate:new --path=@vendor/yiisoft/rbac-db/migrations

# Display new migrations from multiple namespaces
./yii migrate:new --namespace=Yiisoft\\Rbac\\Db\\Migrations --namespace=Yiisoft\\Cache\\Db\\Migrations
```
