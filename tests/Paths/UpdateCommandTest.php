<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Paths;

use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Db\Migration\Tests\TestCase;

/**
 * @group paths
 */
final class UpdateCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        /** Set path for generate migration */
        $this->migrationService->createPath('@yiisoft/yii/db/migration/migration');

        /** Set list path for update migration */
        $this->migrationService->updatePath(['@yiisoft/yii/db/migration/migration', '@root']);
    }

    public function testExecute(): void
    {
        $migrationTable = 'migration';
        $tableMaster = 'department';
        $tableRelation = 'student';

        if ($this->db->getSchema()->getTableSchema($migrationTable) !== null) {
            $this->db->createCommand()->dropTable($migrationTable)->execute();
        }

        if ($this->db->getSchema()->getTableSchema($tableRelation) !== null) {
            $this->db->createCommand()->dropTable($tableRelation)->execute();
        }

        if ($this->db->getSchema()->getTableSchema($tableMaster) !== null) {
            $this->db->createCommand()->dropTable($tableMaster)->execute();
        }

        $create = $this->application->find('generate/create');

        $commandCreate = new CommandTester($create);

        $commandCreate->setInputs(['yes']);
        $commandCreate->execute([
            'name' => $tableMaster,
            '--command' => 'table',
            '--fields' => 'name:string(50):null',
        ]);
        $commandCreate->execute([
            'name' => $tableRelation,
            '--command' => 'table',
            '--fields' => 'name:string(50):null,department_id:integer:notnull:foreignKey(department),dateofbirth:date:null',
        ]);

        $update = $this->application->find('migrate/up');

        $commandUpdate = new CommandTester($update);

        $commandUpdate->setInputs(['yes']);

        $this->assertEquals(ExitCode::OK, $commandUpdate->execute([]));

        $departmentSchema = $this->db->getSchema()->getTableSchema($tableMaster);

        /** Check create table department columns*/
        $this->assertCount(2, $departmentSchema->getColumns());

        /** Check table department field id */
        $this->assertEquals('id', $departmentSchema->getColumn('id')->getName());
        $this->assertEquals('integer', $departmentSchema->getColumn('id')->getType());
        $this->assertTrue($departmentSchema->getColumn('id')->isPrimaryKey());
        $this->assertTrue($departmentSchema->getColumn('id')->isAutoIncrement());

        /** Check table department field name */
        $this->assertEquals('name', $departmentSchema->getColumn('name')->getName());
        $this->assertEquals(50, $departmentSchema->getColumn('name')->getSize());
        $this->assertEquals('string', $departmentSchema->getColumn('name')->getType());
        $this->assertTrue($departmentSchema->getColumn('name')->isAllowNull());

        $studentSchema = $this->db->getSchema()->getTableSchema($tableRelation);

        /** Check create table student columns*/
        $this->assertCount(4, $studentSchema->getColumns());

        /** Check table student field id */
        $this->assertEquals('id', $studentSchema->getColumn('id')->getName());
        $this->assertEquals('integer', $studentSchema->getColumn('id')->getType());
        $this->assertTrue($studentSchema->getColumn('id')->isPrimaryKey());
        $this->assertTrue($studentSchema->getColumn('id')->isAutoIncrement());

        /** Check table student field name */
        $this->assertEquals('name', $studentSchema->getColumn('name')->getName());
        $this->assertEquals('string', $studentSchema->getColumn('name')->getType());
        $this->assertEquals(50, $studentSchema->getColumn('name')->getSize());
        $this->assertTrue($studentSchema->getColumn('name')->isAllowNull());

        /** Check table student field department_id */
        $this->assertEquals('department_id', $studentSchema->getColumn('department_id')->getName());
        $this->assertEquals('integer', $studentSchema->getColumn('department_id')->getType());
        $this->assertFalse($studentSchema->getColumn('department_id')->isAllowNull());
        $this->assertEquals(['department_id'], $this->db->getSchema()->getTableForeignKeys($tableRelation, true)[0]->getColumnNames());

        /** Check table student field dateofbirth */
        $this->assertEquals('dateofbirth', $studentSchema->getColumn('dateofbirth')->getName());
        $this->assertEquals('date', $studentSchema->getColumn('dateofbirth')->getType());
        $this->asserttrue($studentSchema->getColumn('dateofbirth')->isAllowNull());
    }

    public function testExecuteAgain(): void
    {
        $update = $this->application->find('migrate/up');

        $commandUpdate = new CommandTester($update);

        $commandUpdate->setInputs(['yes']);

        $this->assertEquals(ExitCode::UNSPECIFIED_ERROR, $commandUpdate->execute([]));

        $output = $commandUpdate->getDisplay(true);

        $this->assertStringContainsString('No new migrations found.', $output);
    }
}
