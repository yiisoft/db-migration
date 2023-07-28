<?php

declare(strict_types=1);

/**
 * This view is used by Yiisoft\Db\Yii\Migration\Command.
 *
 * The following variables are available in this view:
 *
 * @var $this \Yiisoft\Yii\Db\Migration\Service\Generate\PhpRenderer
 * @var $className string the new migration class name without namespace
 * @var $namespace string the new migration class namespace
 * @var $table string the name table
 * @var $tableComment string the comment table
 * @var $columns \Yiisoft\Yii\Db\Migration\Service\Generate\Column[] the fields
 * @var $foreignKeys \Yiisoft\Yii\Db\Migration\Service\Generate\ForeignKey[] the foreign keys
 * @var $transactional bool Whether the migration should be transactional.
 */

echo "<?php\n";

echo "\ndeclare(strict_types=1);\n";

if (!empty($namespace)) {
    echo "\nnamespace {$namespace};\n";
}
?>

use Yiisoft\Yii\Db\Migration\MigrationBuilder;
<?= $transactional === false ? 'use Yiisoft\Yii\Db\Migration\RevertibleMigrationInterface' : 'use Yiisoft\Yii\Db\Migration\TransactionalMigrationInterface' ?>;

/**
 * Handles the creation of table `<?= $table ?>`.
<?= $this->render(__DIR__ . '/_foreignTables.php', [
    'foreignKeys' => $foreignKeys,
]) ?>
 */
final class <?= $className ?> implements <?= $transactional === false ? "RevertibleMigrationInterface\n" : "TransactionalMigrationInterface\n" ?>
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
