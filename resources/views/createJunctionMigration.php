<?php
/**
 * This view is used by Yiisoft\Db\Yii\Migration\Command.
 */
/* @var $className string the new migration class name without namespace */
/* @var $namespace string the new migration class namespace */
/* @var $table string the name table */
/* @var $field_first string the name field first */
/* @var $field_second string the name field second */

echo "<?php\n";

echo "\ndeclare(strict_types=1);\n";

if (!empty($namespace)) {
    echo "\nnamespace {$namespace};\n";
}
?>

use Yiisoft\Yii\Db\Migration\MigrationHelper;
use Yiisoft\Yii\Db\Migration\RevertibleMigrationInterface;

/**
 * Handles the creation of table `<?= $table ?>` which is a junction between
 * table `<?= $field_first ?>` and table `<?= $field_second ?>`.
 */
final class <?= $className ?> implements RevertibleMigrationInterface
{
    public function up(MigrationHelper $m): void
    {
        $m->createTable('<?= $table ?>', [
            '<?= $field_first ?>_id' => $m->integer(),
            '<?= $field_second ?>_id' => $m->integer(),
            'PRIMARY KEY(<?= $field_first ?>_id, <?= $field_second ?>_id)',
        ]);

        $m->createIndex(
            'idx-<?= $table . '-' . $field_first ?>_id',
            '<?= $table ?>',
            '<?= $field_first ?>_id'
        );

        $m->createIndex(
            'idx-<?= $table . '-' . $field_second ?>_id',
            '<?= $table ?>',
            '<?= $field_second ?>_id'
        );

        $m->addForeignKey(
            'fk-<?= $table . '-' . $field_first ?>_id',
            '<?= $table ?>',
            '<?= $field_first ?>_id',
            '<?= $field_first ?>',
            'id',
            'CASCADE'
        );

        $m->addForeignKey(
            'fk-<?= $table . '-' . $field_second ?>_id',
            '<?= $table ?>',
            '<?= $field_second ?>_id',
            '<?= $field_second ?>',
            'id',
            'CASCADE'
        );
    }

    public function down(MigrationHelper $m): void
    {
        $m->dropTable('<?= $table ?>');
    }
}
