<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Command\Namespaces;

use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Yii\Console\ExitCode;

final class UpdateCommandTest extends NamespacesCommandTest
{
    public function testExecute(): void
    {
        $this->createMigration('Create_Department', 'table', 'department', [
            'name:string(50)',
        ]);
        $this->createMigration('Create_Student', 'table', 'student', [
            'name:string(50)',
            'department_id:integer:notNull:foreignKey(department)',
            'dateofbirth:date',
        ]);

        $command = $this->getCommand();

        $command->setInputs(['yes']);

        $this->assertEquals(ExitCode::OK, $command->execute([]));

        $departmentSchema = $this->getDb()->getSchema()->getTableSchema('department');

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

        $studentSchema = $this->getDb()->getSchema()->getTableSchema('student');

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
        $this->assertEquals(
            ['department_id'],
            $this->getDb()->getSchema()->getTableForeignKeys('student', true)[0]->getColumnNames()
        );

        /** Check table student field dateofbirth */
        $this->assertEquals('dateofbirth', $studentSchema->getColumn('dateofbirth')->getName());
        $this->assertEquals('date', $studentSchema->getColumn('dateofbirth')->getType());
        $this->asserttrue($studentSchema->getColumn('dateofbirth')->isAllowNull());
    }

    public function testExecuteAgain(): void
    {
        $command = $this->getCommand();

        $command->setInputs(['yes']);

        $this->assertEquals(ExitCode::UNSPECIFIED_ERROR, $command->execute([]));

        $output = $command->getDisplay(true);

        $this->assertStringContainsString('No new migrations found.', $output);
    }

    public function testWithoutUpdateNamespaces(): void
    {
        $this->getMigrationService()->updateNamespace([]);

        $command = $this->getCommand();

        $exitCode = $command->execute([]);

        $this->assertSame(ExitCode::DATAERR, $exitCode);
    }

    public function testLimit(): void
    {
        $this->createMigration('Create_Post', 'table', 'post', ['name:string']);
        $this->createMigration('Create_User', 'table', 'user', ['name:string']);

        $command = $this->getCommand();

        $exitCode = $command->execute(['-l' => 1]);

        $this->assertSame(ExitCode::OK, $exitCode);
        $this->assertExistsTables('post');
        $this->assertNotExistsTables('user');
    }

    public function testNameLimit(): void
    {
        $this->createMigration('Create_Post_' . str_repeat('X', 200), 'table', 'post', ['name:string']);

        $command = $this->getCommand();

        $exitCode = $command->execute([]);

        $this->assertSame(ExitCode::UNSPECIFIED_ERROR, $exitCode);
    }

    private function getCommand(): CommandTester
    {
        return new CommandTester(
            $this->getApplication()->find('migrate/up')
        );
    }
}
