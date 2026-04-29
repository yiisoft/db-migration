# Criando migrações

## Gerando arquivos de migração

O comando `migrate:create` gera um novo arquivo de migração em um dos caminhos ou namespaces de origem configurados.

### Migração básica

```shell
./yii migrate:create my_migration
```

Isso gera uma classe de migração esqueleto com métodos `up()` e `down()` vazios:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

final class M250101120000MyMigration implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        // TODO: Implementar a lógica para aplicar a migração.
    }

    public function down(MigrationBuilder $b): void
    {
        // TODO: Implementar a lógica para reverter a migração.
    }
}
```

### Migração para criar tabela

```shell
./yii migrate:create post --command=table --fields="title:string,body:text"
```

Isso gera uma classe de migração que cria a tabela `post` com colunas `title` e `body`.

### Migração para remover tabela

```shell
./yii migrate:create post --command=dropTable --fields="title:string,body:text"
```

### Migração para adicionar coluna

```shell
./yii migrate:create post --command=addColumn --fields="position:integer"
```

### Migração para remover coluna

```shell
./yii migrate:create post --command=dropColumn --fields="position:integer"
```

### Migração para tabela de junção

```shell
./yii migrate:create post --command=junction --and=tag
```

Isso gera uma migração para a tabela de junção `post_tag` que relaciona as tabelas `post` e `tag`.

## Sintaxe de campos

A opção `--fields` aceita uma lista de campos separada por vírgula no formato:

```
name:type:decorator1:decorator2
```

Por exemplo:

```shell
./yii migrate:create post --command=table --fields="title:string(100):notNull,body:text,created_at:datetime:notNull,views:integer:notNull:defaultValue(0)"
```

### Tipos de colunas disponíveis

Os seguintes tipos de colunas correspondem aos métodos estáticos do `ColumnBuilder` (o tipo SQL real depende do SGBD usado):

| Tipo | Descrição |
|------|-----------|
| `primaryKey` | Chave primária inteira auto-incremental |
| `bigPrimaryKey` | Chave primária bigint auto-incremental |
| `char` | String de tamanho fixo |
| `string` | String de tamanho variável (padrão 255) |
| `text` | Texto longo |
| `tinyint` | Inteiro de 1 byte |
| `smallint` | Inteiro de 2 bytes |
| `integer` | Inteiro de 4 bytes |
| `bigint` | Inteiro de 8 bytes |
| `float` | Número de ponto flutuante |
| `double` | Número de ponto flutuante de dupla precisão |
| `decimal` | Número decimal |
| `datetime` | Data e hora |
| `timestamp` | Timestamp |
| `time` | Hora |
| `date` | Data |
| `binary` | Dados binários |
| `boolean` | Valor booleano |
| `json` | Dados JSON |

### Decoradores disponíveis

Decoradores são usados para definir propriedades da coluna. Eles são encadeados após o tipo.
Os parênteses podem ser omitidos para decoradores sem argumentos (ex.: `notNull` é equivalente a `notNull()`).

| Decorador | Descrição |
|-----------|-----------|
| `notNull()` | Coluna não permite valores NULL |
| `null()` | Coluna permite valores NULL |
| `unique()` | O valor da coluna deve ser único |
| `unsigned()` | Tipo inteiro sem sinal |
| `defaultValue(value)` | Valor padrão da coluna |
| `check(expression)` | Restrição de verificação |
| `comment(text)` | Comentário da coluna |
| `primaryKey()` | Marcar como chave primária |
| `foreignKey(table column)` | Criar restrição de chave estrangeira |

### Chaves estrangeiras

Para adicionar uma chave estrangeira, use o decorador `foreignKey` com a tabela e coluna referenciadas:

```shell
./yii migrate:create post --command=table --fields="author_id:integer:notNull:foreignKey(user id)"
```

Se o nome da coluna terminar com `_id`, o nome da tabela é derivado automaticamente (por exemplo, `author_id` referencia a tabela `author`).

## Migrações com namespace

Para gerar uma migração com namespace, use a opção `--namespace`:

```shell
./yii migrate:create post --command=table --namespace=App\\Migrations
```

Ou especifique o caminho para o diretório de migrações:

```shell
./yii migrate:create post --command=table --path=@root/migrations/blog
```

## Escrevendo a lógica da migração

Após gerar um arquivo de migração, você implementa a lógica real usando a instância `MigrationBuilder` passada para `up()` e `down()`.

### Criando uma tabela

```php
public function up(MigrationBuilder $b): void
{
    $columnBuilder = $b->columnBuilder();

    $b->createTable('post', [
        'id' => $columnBuilder::primaryKey(),
        'title' => $columnBuilder::string(255)->notNull(),
        'body' => $columnBuilder::text()->notNull(),
        'created_at' => $columnBuilder::datetime()->notNull(),
        'updated_at' => $columnBuilder::datetime(),
    ]);
}

public function down(MigrationBuilder $b): void
{
    $b->dropTable('post');
}
```

### Adicionando e removendo colunas

```php
public function up(MigrationBuilder $b): void
{
    $columnBuilder = $b->columnBuilder();

    $b->addColumn('post', 'views', $columnBuilder::integer()->notNull()->defaultValue(0));
}

public function down(MigrationBuilder $b): void
{
    $b->dropColumn('post', 'views');
}
```

### Adicionando chaves estrangeiras

```php
public function up(MigrationBuilder $b): void
{
    $b->addForeignKey(
        'post',
        'fk-post-author_id',
        'author_id',
        'user',
        'id',
        'CASCADE',
        'CASCADE',
    );
}

public function down(MigrationBuilder $b): void
{
    $b->dropForeignKey('post', 'fk-post-author_id');
}
```

### Criando índices

```php
public function up(MigrationBuilder $b): void
{
    $b->createIndex('post', 'idx-post-title', 'title');
    $b->createIndex('post', 'idx-post-author_id-status', ['author_id', 'status']);
}

public function down(MigrationBuilder $b): void
{
    $b->dropIndex('post', 'idx-post-author_id-status');
    $b->dropIndex('post', 'idx-post-title');
}
```

### Inserindo dados

```php
public function up(MigrationBuilder $b): void
{
    $b->insert('settings', ['key' => 'default_language', 'value' => 'en']);
    $b->batchInsert('settings', ['key', 'value'], [
        ['theme', 'default'],
        ['timezone', 'UTC'],
    ]);
}
```

### Executando SQL bruto

```php
public function up(MigrationBuilder $b): void
{
    $b->execute('ALTER TABLE post ADD FULLTEXT INDEX idx_title (title)');
}
```

## Métodos disponíveis do `MigrationBuilder`

| Método | Descrição |
|--------|-----------|
| `execute($sql, $params)` | Executa um comando SQL bruto |
| `insert($table, $columns)` | Insere uma linha em uma tabela |
| `batchInsert($table, $columns, $rows)` | Insere múltiplas linhas em uma tabela |
| `upsert($table, $insertColumns, $updateColumns)` | Insere ou atualiza uma linha |
| `update($table, $columns, $condition = '', $from = null, $params = [])` | Atualiza linhas em uma tabela |
| `delete($table, $condition = '', $params = [])` | Remove linhas de uma tabela |
| `createTable($table, $columns)` | Cria uma nova tabela |
| `renameTable($table, $newName)` | Renomeia uma tabela |
| `dropTable($table)` | Remove uma tabela |
| `truncateTable($table)` | Trunca uma tabela |
| `addColumn($table, $column, $type)` | Adiciona uma coluna a uma tabela |
| `dropColumn($table, $column)` | Remove uma coluna de uma tabela |
| `renameColumn($table, $name, $newName)` | Renomeia uma coluna |
| `alterColumn($table, $column, $type)` | Altera a definição de uma coluna |
| `addPrimaryKey($table, $name, $columns)` | Adiciona uma restrição de chave primária |
| `dropPrimaryKey($table, $name)` | Remove uma restrição de chave primária |
| `addForeignKey($table, $name, $columns, $referenceTable, $referenceColumns, $delete, $update)` | Adiciona uma restrição de chave estrangeira |
| `dropForeignKey($table, $name)` | Remove uma restrição de chave estrangeira |
| `createIndex($table, $name, $columns, $indexType, $indexMethod)` | Cria um índice |
| `dropIndex($table, $name)` | Remove um índice |
| `createView($viewName, $subQuery)` | Cria uma visão |
| `dropView($viewName)` | Remove uma visão |
| `addCommentOnColumn($table, $column, $comment)` | Adiciona um comentário em uma coluna |
| `addCommentOnTable($table, $comment)` | Adiciona um comentário em uma tabela |
| `dropCommentFromColumn($table, $column)` | Remove um comentário de uma coluna |
| `dropCommentFromTable($table)` | Remove um comentário de uma tabela |
