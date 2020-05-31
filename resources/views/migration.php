<?php
/**
 * This view is used by Yiisoft\Db\Yii\Migration\Command.
 *
 * The following variables are available in this view:
 */
/* @var $className string the new migration class name without namespace */
/* @var $namespace string the new migration class namespace */

echo "<?php\n";

echo "\ndeclare(strict_types=1);\n";

if (!empty($namespace)) {
    echo "\nnamespace {$namespace};\n";
}
?>

use Yiisoft\Yii\Db\Migration\Migration;

/**
 * Class <?= $className . "\n" ?>
 */
class <?= $className ?> extends Migration
{
    public function up(): void
    {

    }

    public function down(): void
    {
        throw new \RuntimeException('Cannot revert migration. down() is not implemented.');
    }
}
