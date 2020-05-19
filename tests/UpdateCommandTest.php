<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Widget;

use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Yii\Db\Migration\Tests\TestCase;

final class UpdateCommandTest extends TestCase
{
    public function testExecute(): void
    {
        $tableMaster = 'department';
        $tableRelation = 'student';

        $create = $this->application->find('generate/create');

        $commandCreate = new CommandTester($create);

        $commandCreate->setInputs(['yes']);
        $commandCreate->execute([
            'name' => $tableMaster,
            '--command' => 'table',
            '--fields' => 'name:string(50):null'
        ]);
        $commandCreate->execute([
            'name' => $tableRelation,
            '--command' => 'table',
            '--fields' => 'name:string(50):null,department_id:integer:notnull:foreignKey(department),dateofbirth:date:null'
        ]);

        $update = $this->application->find('migrate/up');

        $commandUpdate = new CommandTester($update);

        $commandUpdate->setInputs(['yes']);
        $commandUpdate->execute([]);
        $columsDeparment = $this->db->getSchema()->getTableSchema($tableMaster);

        /** Check create table deparment columns*/
        $this->assertEquals(2, count($columsDeparment->getColumns()));

        /** Check table deparment field id */
        $this->assertEquals('id', $columsDeparment->getColumn('id')->getName());
        $this->assertEquals('integer', $columsDeparment->getColumn('id')->getType());
        $this->assertTrue($columsDeparment->getColumn('id')->isPrimaryKey());
        $this->assertTrue($columsDeparment->getColumn('id')->isAutoIncrement());

        /** Check table deparment field name */
        $this->assertEquals('name', $columsDeparment->getColumn('name')->getName());
        $this->assertEquals(50, $columsDeparment->getColumn('name')->getSize());
        $this->assertEquals('string', $columsDeparment->getColumn('name')->getType());
        $this->assertTrue($columsDeparment->getColumn('name')->isAllowNull());

        $columsStudent = $this->db->getSchema()->getTableSchema($tableRelation);

        /** Check create table student columns*/
        $this->assertEquals(4, count($columsStudent->getColumns()));

        /** Check table student field id */
        $this->assertEquals('id', $columsStudent->getColumn('id')->getName());
        $this->assertEquals('integer', $columsStudent->getColumn('id')->getType());
        $this->assertTrue($columsStudent->getColumn('id')->isPrimaryKey());
        $this->assertTrue($columsStudent->getColumn('id')->isAutoIncrement());

        /** Check table student field name */
        $this->assertEquals('name', $columsStudent->getColumn('name')->getName());
        $this->assertEquals('string', $columsStudent->getColumn('name')->getType());
        $this->assertEquals(50, $columsStudent->getColumn('name')->getSize());
        $this->assertTrue($columsStudent->getColumn('name')->isAllowNull());

        /** Check table student field department_id */
        $this->assertEquals('department_id', $columsStudent->getColumn('department_id')->getName());
        $this->assertEquals('integer', $columsStudent->getColumn('department_id')->getType());
        $this->assertFalse($columsStudent->getColumn('department_id')->isAllowNull());
        $this->assertEquals(['department_id'], $this->db->getSchema()->getTableForeignKeys($tableRelation, true)[0]->getColumnNames());

        /** Check table student field dateofbirth */
        $this->assertEquals('dateofbirth', $columsStudent->getColumn('dateofbirth')->getName());
        $this->assertEquals('date', $columsStudent->getColumn('dateofbirth')->getType());
        $this->asserttrue($columsStudent->getColumn('dateofbirth')->isAllowNull());
    }

    public function testExecuteUpdate(): void
    {
        $tableMaster = 'department';
        $tableRelation = 'student';

        $update = $this->application->find('migrate/up');

        $commandUpdate = new CommandTester($update);

        $commandUpdate->setInputs(['yes']);
        $commandUpdate->execute([]);

        $output = $commandUpdate->getDisplay();

        $this->assertStringContainsString('>>> No new migrations found.', $output);
    }
}
