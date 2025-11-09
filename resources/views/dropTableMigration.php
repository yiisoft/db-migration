<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\Service\Generate\Column;
use Yiisoft\Db\Migration\Service\Generate\ForeignKey;
use Yiisoft\Db\Migration\Service\Generate\PhpRenderer;

/**
 * This view is used by {@see Yiisoft\Db\Migration\Command\CreateCommand}.
 *
 * The following variables are available in this view:
 *
 * @var PhpRenderer $this
 * @var string $className The new migration class name without namespace.
 * @var string $namespace The new migration class namespace.
 * @var string $table The table name.
 * @var Column[] $columns Fields.
 * @var ForeignKey[] $foreignKeys Foreign keys.
 */

echo "<?php\n";

echo "\ndeclare(strict_types=1);\n";

if (!empty($namespace)) {
    echo "\nnamespace {$namespace};\n";
}
?>

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * Handles the dropping of table `<?= $table ?>`.
<?= $this->render(__DIR__ . '/_foreignTables.php', [
    'foreignKeys' => $foreignKeys,
]) ?>
 */
final class <?= $className ?> implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
<?= $this->render(__DIR__ . '/_dropTable.php', [
    'table' => $table,
    'foreignKeys' => $foreignKeys,
])
?>
    }

    public function down(MigrationBuilder $b): void
    {
<?= $this->render(__DIR__ . '/_createTable.php', [
    'table' => $table,
    'columns' => $columns,
    'foreignKeys' => $foreignKeys,
])
?>
    }
}
