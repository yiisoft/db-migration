# Referência de comandos

## `migrate:create`

Cria um novo arquivo de migração.

```shell
./yii migrate:create <name> [options]
```

### Argumentos

| Argumento | Descrição |
|-----------|-----------|
| `name` | O nome da tabela ou da migração para gerar. |

### Opções

| Opção | Atalho | Descrição | Padrão |
|-------|--------|-----------|--------|
| `--command` | `-c` | O tipo de migração a gerar. Valores disponíveis: `create`, `table`, `dropTable`, `addColumn`, `dropColumn`, `junction`. | `create` |
| `--fields` | `-f` | Definições de campo separadas por vírgula para a migração (ex.: `title:string,body:text`). | |
| `--table-comment` | | Comentário a adicionar na tabela. | |
| `--and` | | O nome da segunda tabela para uma migração `junction`. | |
| `--path` | | Caminho para o diretório onde o novo arquivo de migração será criado. | |
| `--namespace` | `-ns` | Namespace da nova classe de migração. | |

### Exemplos

```shell
# Criar um esqueleto de migração básico
./yii migrate:create my_migration

# Criar uma migração para uma nova tabela com campos
./yii migrate:create post --command=table --fields="title:string:notNull,body:text,created_at:datetime:notNull"

# Criar uma migração para remover uma tabela (informando os campos para que o down() a recrie corretamente)
./yii migrate:create post --command=dropTable --fields="title:string:notNull,body:text,created_at:datetime:notNull"

# Criar uma migração para adicionar uma coluna
./yii migrate:create post --command=addColumn --fields="views:integer:notNull:defaultValue(0)"

# Criar uma migração para remover uma coluna
./yii migrate:create post --command=dropColumn --fields="views:integer"

# Criar uma migração de tabela de junção
./yii migrate:create post --command=junction --and=tag

# Criar uma migração com namespace
./yii migrate:create post --command=table --namespace=App\\Migrations

# Criar uma migração em um diretório específico
./yii migrate:create post --command=table --path=@root/migrations/blog
```

---

## `migrate:up`

Aplica novas migrações (ainda não aplicadas).

```shell
./yii migrate:up [options]
```

### Opções

| Opção | Atalho | Descrição | Padrão |
|-------|--------|-----------|--------|
| `--limit` | `-l` | O número de migrações a aplicar. Aplica todas as novas migrações se não especificado. | |
| `--path` | | Caminho(s) para diretórios de migração a aplicar. Pode ser especificado múltiplas vezes. | |
| `--namespace` | `-ns` | Namespace(s) de migrações a aplicar. Pode ser especificado múltiplas vezes. | |
| `--force-yes` | `-y` | Pular a confirmação. | |

### Exemplos

```shell
# Aplicar todas as novas migrações
./yii migrate:up

# Aplicar as primeiras 3 novas migrações
./yii migrate:up --limit=3

# Aplicar novas migrações de um diretório específico
./yii migrate:up --path=@vendor/yiisoft/rbac-db/migrations

# Aplicar novas migrações de um namespace específico
./yii migrate:up --namespace=Yiisoft\\Rbac\\Db\\Migrations

# Aplicar novas migrações de múltiplos diretórios
./yii migrate:up --path=@vendor/yiisoft/rbac-db/migrations --path=@vendor/yiisoft/cache-db/migrations

# Aplicar sem confirmação
./yii migrate:up --force-yes
```

---

## `migrate:down`

Reverte migrações previamente aplicadas.

```shell
./yii migrate:down [options]
```

### Opções

| Opção | Atalho | Descrição | Padrão |
|-------|--------|-----------|--------|
| `--limit` | `-l` | O número de migrações a reverter. | `1` |
| `--all` | `-a` | Reverter todas as migrações aplicadas. | |
| `--path` | | Caminho(s) para diretórios de migração a reverter. Pode ser especificado múltiplas vezes. | |
| `--namespace` | `-ns` | Namespace(s) de migrações a reverter. Pode ser especificado múltiplas vezes. | |
| `--force-yes` | `-y` | Pular a confirmação. | |

### Exemplos

```shell
# Reverter a última migração aplicada
./yii migrate:down

# Reverter as últimas 3 migrações aplicadas
./yii migrate:down --limit=3

# Reverter todas as migrações aplicadas
./yii migrate:down --all

# Reverter a última migração de um diretório específico
./yii migrate:down --path=@vendor/yiisoft/rbac-db/migrations

# Reverter a última migração de um namespace específico
./yii migrate:down --namespace=Yiisoft\\Rbac\\Db\\Migrations
```

---

## `migrate:redo`

Reverte e reaplica a(s) última(s) migração(ões).

```shell
./yii migrate:redo [options]
```

### Opções

| Opção | Atalho | Descrição | Padrão |
|-------|--------|-----------|--------|
| `--limit` | `-l` | O número de migrações a refazer. | `1` |
| `--all` | `-a` | Refazer todas as migrações aplicadas. | |
| `--path` | | Caminho(s) para diretórios de migração a refazer. Pode ser especificado múltiplas vezes. | |
| `--namespace` | `-ns` | Namespace(s) de migrações a refazer. Pode ser especificado múltiplas vezes. | |
| `--force-yes` | `-y` | Pular a confirmação. | |

### Exemplos

```shell
# Refazer a última migração aplicada
./yii migrate:redo

# Refazer as últimas 3 migrações aplicadas
./yii migrate:redo --limit=3

# Refazer todas as migrações aplicadas
./yii migrate:redo --all

# Refazer a última migração de um diretório específico
./yii migrate:redo --path=@vendor/yiisoft/rbac-db/migrations
```

---

## `migrate:history`

Exibe a lista de migrações aplicadas.

```shell
./yii migrate:history [options]
```

### Opções

| Opção | Atalho | Descrição | Padrão |
|-------|--------|-----------|--------|
| `--limit` | `-l` | O número de migrações a exibir. | `10` |
| `--all` | `-a` | Exibir todas as migrações aplicadas. | |

### Exemplos

```shell
# Exibir as últimas 10 migrações aplicadas
./yii migrate:history

# Exibir as últimas 5 migrações aplicadas
./yii migrate:history --limit=5

# Exibir todas as migrações aplicadas
./yii migrate:history --all
```

---

## `migrate:new`

Exibe a lista de novas migrações (ainda não aplicadas).

```shell
./yii migrate:new [options]
```

### Opções

| Opção | Atalho | Descrição | Padrão |
|-------|--------|-----------|--------|
| `--limit` | `-l` | O número de migrações a exibir. | `10` |
| `--all` | `-a` | Exibir todas as novas migrações. | |
| `--path` | | Caminho(s) para diretórios de migração a verificar. Pode ser especificado múltiplas vezes. | |
| `--namespace` | `-ns` | Namespace(s) de migrações a verificar. Pode ser especificado múltiplas vezes. | |

### Exemplos

```shell
# Exibir as primeiras 10 novas migrações
./yii migrate:new

# Exibir as primeiras 5 novas migrações
./yii migrate:new --limit=5

# Exibir todas as novas migrações
./yii migrate:new --all

# Exibir novas migrações de um diretório específico
./yii migrate:new --path=@vendor/yiisoft/rbac-db/migrations

# Exibir novas migrações de múltiplos namespaces
./yii migrate:new --namespace=Yiisoft\\Rbac\\Db\\Migrations --namespace=Yiisoft\\Cache\\Db\\Migrations
```
