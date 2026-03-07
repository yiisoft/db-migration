# Tipos de migração

O pacote fornece diversas interfaces para implementar migrações. A escolha da interface correta depende se você
precisa que a migração seja reversível e/ou envolvida em uma transação.

## `MigrationInterface`

A interface base para todas as migrações. Requer apenas a implementação do método `up()`, que contém a lógica para
aplicar a migração.

```php
use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\MigrationInterface;

final class M250101120000CreatePostTable implements MigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $columnBuilder = $b->columnBuilder();

        $b->createTable('post', [
            'id' => $columnBuilder::primaryKey(),
            'title' => $columnBuilder::string(255)->notNull(),
        ]);
    }
}
```

Use esta interface quando a migração não precisar ser revertida.

## `RevertibleMigrationInterface`

Estende `MigrationInterface` e adiciona o método `down()` para reverter a migração. Esta é a interface mais utilizada.

```php
use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

final class M250101120000CreatePostTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $columnBuilder = $b->columnBuilder();

        $b->createTable('post', [
            'id' => $columnBuilder::primaryKey(),
            'title' => $columnBuilder::string(255)->notNull(),
        ]);
    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropTable('post');
    }
}
```

Use esta interface quando quiser poder reverter a migração usando `migrate:down` ou `migrate:redo`.

## `TransactionalMigrationInterface`

Estende `MigrationInterface` e faz com que a migração seja envolvida em uma transação de banco de dados. Se qualquer
etapa da migração falhar, todas as alterações feitas pela migração serão revertidas.

```php
use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;
use Yiisoft\Db\Migration\TransactionalMigrationInterface;

final class M250101120000CreatePostTable implements RevertibleMigrationInterface, TransactionalMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $columnBuilder = $b->columnBuilder();

        $b->createTable('post', [
            'id' => $columnBuilder::primaryKey(),
            'title' => $columnBuilder::string(255)->notNull(),
        ]);
    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropTable('post');
    }
}
```

Use esta interface em combinação com `MigrationInterface` ou `RevertibleMigrationInterface` quando quiser que a
migração seja executada dentro de uma transação. Note que nem todos os SGBDs suportam DDL transacional. Por exemplo,
o MySQL faz commit implícito de declarações DDL e elas não podem ser revertidas.

## Comparação

| Interface | `up()` | `down()` | Transacional |
|-----------|--------|----------|--------------|
| `MigrationInterface` | ✓ | ✗ | ✗ |
| `RevertibleMigrationInterface` | ✓ | ✓ | ✗ |
| `MigrationInterface` + `TransactionalMigrationInterface` | ✓ | ✗ | ✓ |
| `RevertibleMigrationInterface` + `TransactionalMigrationInterface` | ✓ | ✓ | ✓ |
