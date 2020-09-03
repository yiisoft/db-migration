<?php
declare(strict_types=1);
namespace Yiisoft\Yii\Db\Migration\Tests\NamespaceMigration;

use Yiisoft\Yii\Db\Migration\Migration;

/**
 * Handles the creation of table `department`.
 */
final class M200903153847CreateDepartmentTable extends Migration
{
    public function up(): void
    {
        $this->createTable('department', [
            'id' => $this->primaryKey(),
            'name' => $this->string(50)->null(),
        ]);
    }

    public function down(): void
    {
        $this->dropTable('department');
    }
}
