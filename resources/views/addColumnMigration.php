<?php
/**
 * This view is used by Yiisoft\Db\Yii\Migration\Command.
 *
 * The following variables are available in this view:
 */
/* @var $className string the new migration class name without namespace */
/* @var $namespace string the new migration class namespace */
/* @var $table string the name table */
/* @var $fields array the fields */

echo "<?php\n";

echo "\ndeclare(strict_types=1);\n";

if (!empty($namespace)) {
    echo "\nnamespace {$namespace};\n";
}
?>

use Yiisoft\Yii\Db\Migration\Migration;

/**
 * Handles adding columns to table `<?= $table ?>`.
<?= $this->render('_foreignTables', [
     'foreignKeys' => $foreignKeys,
 ]) ?>
 */
class <?= $className ?> extends Migration
{
    public function up(): void
    {
<?= $this->render('_addColumns', [
    'table' => $table,
    'fields' => $fields,
    'foreignKeys' => $foreignKeys,
])
?>
    }

    public function down(): void
    {
<?= $this->render('_dropColumns', [
    'table' => $table,
    'fields' => $fields,
    'foreignKeys' => $foreignKeys,
])
?>
    }
}
