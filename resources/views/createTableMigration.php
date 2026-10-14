<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\Service\Generate\Column;
use Yiisoft\Db\Migration\Service\Generate\ForeignKey;
use Yiisoft\Db\Migration\Service\Generate\PhpRenderer;
use Yiisoft\Db\Migration\Command\CreateCommand;

/**
 * This view is used by {@see CreateCommand}.
 *
 * The following variables are available in this view:
 *
 * @var PhpRenderer $this
 * @var string $className The new migration class name without namespace.
 * @var string $namespace The new migration class namespace.
 * @var bool|null $transactional Whether the migration uses transactions. `null` keeps the template default.
 * @var string $table The table name.
 * @var string $tableComment The table comment.
 * @var Column[] $columns Fields.
 * @var ForeignKey[] $foreignKeys Foreign keys.
 */

$transactional ??= true;

echo "<?php\n";

echo "\ndeclare(strict_types=1);\n";

if (!empty($namespace)) {
    echo "\nnamespace {$namespace};\n";
}
?>

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;
<?php if ($transactional): ?>
use Yiisoft\Db\Migration\TransactionalMigrationInterface;
<?php endif; ?>

/**
 * Handles the creation of table `<?= $table ?>`.
<?= $this->render(__DIR__ . '/_foreignTables.php', [
    'foreignKeys' => $foreignKeys,
]) ?>
 */
final class <?= $className ?> implements RevertibleMigrationInterface<?=
    $transactional ? ", TransactionalMigrationInterface\n" : "\n"
?>
{
    public function up(MigrationBuilder $b): void
    {
<?= $this->render(__DIR__ . '/_createTable.php', [
    'table' => $table,
    'columns' => $columns,
    'foreignKeys' => $foreignKeys,
])
?>
<?php if (!empty($tableComment)) {
    echo $this->render(__DIR__ . '/_addComments.php', [
        'table' => $table,
        'tableComment' => $tableComment,
    ]);
}
?>
    }

    public function down(MigrationBuilder $b): void
    {
<?= $this->render(__DIR__ . '/_dropTable.php', [
    'table' => $table,
    'foreignKeys' => $foreignKeys,
])
?>
    }
}
