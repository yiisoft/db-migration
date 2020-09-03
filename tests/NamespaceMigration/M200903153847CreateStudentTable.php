<?php
declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\NamespaceMigration;

use Yiisoft\Yii\Db\Migration\Migration;

/**
 * Handles the creation of table `student`.
 * Has foreign keys to the tables:
 *
 * - `{{%department}}`
 */
final class M200903153847CreateStudentTable extends Migration
{
    public function up(): void
    {
        $this->createTable('student', [
            'id' => $this->primaryKey(),
            'name' => $this->string(50)->null(),
            'department_id' => $this->integer()->notnull(),
            'dateofbirth' => $this->date()->null(),
        ]);

        // creates index for column `department_id`
        $this->createIndex(
            'idx-student-department_id',
            'student',
            'department_id'
        );

        // add foreign key for table `{{%department}}`
        $this->addForeignKey(
            'fk-student-department_id',
            'student',
            'department_id',
            '{{%department}}',
            'id',
            'CASCADE'
        );
    }

    public function down(): void
    {
        // drops foreign key for table `{{%department}}`
        $this->dropForeignKey(
            'fk-student-department_id',
            'student'
        );

        // drops index for column `department_id`
        $this->dropIndex(
            'idx-student-department_id',
            'student'
        );

        $this->dropTable('student');
    }
}
