<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Command;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Db\Pgsql\Connection as PgSqlConnection;
use Yiisoft\Db\Sqlite\Connection as SqLiteConnection;
use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Db\Migration\Command\UpdateCommand;
use Yiisoft\Yii\Db\Migration\Tests\Support\CommandHelper;
use Yiisoft\Yii\Db\Migration\Tests\Support\MigrationHelper;
use Yiisoft\Yii\Db\Migration\Tests\Support\PostgreSqlHelper;
use Yiisoft\Yii\Db\Migration\Tests\Support\SqLiteHelper;

final class UpdateCommandTest extends TestCase
{
    public function testExecuteWithPath(): void
    {
        $container = SqLiteHelper::createContainer();
        MigrationHelper::useMigrationsPath($container);
        SqLiteHelper::clearDatabase($container);

        MigrationHelper::createMigration(
            $container,
            'Create_Department',
            'table',
            'department',
            ['name:string(50)'],
        );

        $command = $this->createCommand($container);
        $command->setInputs(['yes']);

        $exitCode = $command->execute([]);

        $dbSchema = $container->get(SqLiteConnection::class)->getSchema();
        $departmentSchema = $dbSchema->getTableSchema('department');

        $this->assertSame(ExitCode::OK, $exitCode);

        /** Check create table department columns*/
        $this->assertCount(2, $departmentSchema->getColumns());

        /** Check table department field id */
        $this->assertSame('id', $departmentSchema->getColumn('id')->getName());
        $this->assertSame('integer', $departmentSchema->getColumn('id')->getType());
        $this->assertTrue($departmentSchema->getColumn('id')->isPrimaryKey());
        $this->assertTrue($departmentSchema->getColumn('id')->isAutoIncrement());

        /** Check table department field name */
        $this->assertSame('name', $departmentSchema->getColumn('name')->getName());
        $this->assertSame(50, $departmentSchema->getColumn('name')->getSize());
        $this->assertSame('string', $departmentSchema->getColumn('name')->getType());
        $this->assertTrue($departmentSchema->getColumn('name')->isAllowNull());
    }

    public function testExecuteWithNamespace(): void
    {
        $container = SqLiteHelper::createContainer();
        MigrationHelper::useMigrationsNamespace($container);
        SqLiteHelper::clearDatabase($container);

        MigrationHelper::createMigration(
            $container,
            'Create_Department',
            'table',
            'department',
            ['name:string(50)']
        );

        $command = $this->createCommand($container);
        $command->setInputs(['yes']);

        $exitCode = $command->execute([]);

        $dbSchema = $container->get(SqLiteConnection::class)->getSchema();
        $departmentSchema = $dbSchema->getTableSchema('department');

        $this->assertSame(ExitCode::OK, $exitCode);

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
    }

    public function testExecuteExtended(): void
    {
        $container = PostgreSqlHelper::createContainer();
        MigrationHelper::useMigrationsPath($container);
        PostgreSqlHelper::clearDatabase($container);

        MigrationHelper::createMigration(
            $container,
            'Create_Department',
            'table',
            'department',
            ['name:string(50)'],
        );

        MigrationHelper::createMigration(
            $container,
            'Create_Student',
            'table',
            'student',
            [
                'name:string(50):comment("Student Name")',
                'department_id:integer:notNull:foreignKey(department)',
                'dateofbirth:date',
            ],
        );

        $command = $this->createCommand($container);
        $command->setInputs(['yes']);

        $exitCode = $command->execute([]);

        $dbSchema = $container->get(PgSqlConnection::class)->getSchema();
        $departmentSchema = $dbSchema->getTableSchema('department');
        $studentSchema = $dbSchema->getTableSchema('student');

        $this->assertSame(ExitCode::OK, $exitCode);

        /** Check create table department columns*/
        $this->assertCount(2, $departmentSchema->getColumns());

        /** Check table department field id */
        $this->assertSame('id', $departmentSchema->getColumn('id')->getName());
        $this->assertSame('integer', $departmentSchema->getColumn('id')->getType());
        $this->assertTrue($departmentSchema->getColumn('id')->isPrimaryKey());
        $this->assertTrue($departmentSchema->getColumn('id')->isAutoIncrement());

        /** Check table department field name */
        $this->assertSame('name', $departmentSchema->getColumn('name')->getName());
        $this->assertSame(50, $departmentSchema->getColumn('name')->getSize());
        $this->assertSame('string', $departmentSchema->getColumn('name')->getType());
        $this->assertTrue($departmentSchema->getColumn('name')->isAllowNull());

        /** Check create table student columns*/
        $this->assertCount(4, $studentSchema->getColumns());

        /** Check table student field id */
        $this->assertSame('id', $studentSchema->getColumn('id')->getName());
        $this->assertSame('integer', $studentSchema->getColumn('id')->getType());
        $this->assertTrue($studentSchema->getColumn('id')->isPrimaryKey());
        $this->assertTrue($studentSchema->getColumn('id')->isAutoIncrement());

        /** Check table student field name */
        $this->assertSame('name', $studentSchema->getColumn('name')->getName());
        $this->assertSame('string', $studentSchema->getColumn('name')->getType());
        $this->assertSame(50, $studentSchema->getColumn('name')->getSize());
        $this->assertTrue($studentSchema->getColumn('name')->isAllowNull());

        /** Check table student field department_id */
        $this->assertSame('department_id', $studentSchema->getColumn('department_id')->getName());
        $this->assertSame('integer', $studentSchema->getColumn('department_id')->getType());
        $this->assertSame('Student Name', $studentSchema->getColumn('name')->getComment());
        $this->assertFalse($studentSchema->getColumn('department_id')->isAllowNull());
        $this->assertSame(
            ['department_id'],
            $dbSchema->getTableForeignKeys('student', true)[0]->getColumnNames()
        );

        /** Check table student field dateofbirth */
        $this->assertSame('dateofbirth', $studentSchema->getColumn('dateofbirth')->getName());
        $this->assertSame('date', $studentSchema->getColumn('dateofbirth')->getType());
        $this->asserttrue($studentSchema->getColumn('dateofbirth')->isAllowNull());
    }

    public function testExecuteAgain(): void
    {
        $container = SqLiteHelper::createContainer();
        MigrationHelper::useMigrationsNamespace($container);
        SqLiteHelper::clearDatabase($container);

        MigrationHelper::createMigration(
            $container,
            'Create_Department',
            'table',
            'department',
            ['name:string(50)']
        );

        $command1 = $this->createCommand($container);
        $command1->setInputs(['yes']);

        $exitCode1 = $command1->execute([]);
        $output1 = $command1->getDisplay(true);

        $command2 = $this->createCommand($container);
        $command2->setInputs(['yes']);

        $exitCode2 = $command2->execute([]);
        $output2 = $command2->getDisplay(true);

        $this->assertSame(ExitCode::OK, $exitCode1);
        $this->assertStringContainsString('1 Migration was applied.', $output1);

        $this->assertSame(ExitCode::OK, $exitCode2);
        $this->assertStringContainsString('No new migrations found.', $output2);
    }

    public function createCommand(ContainerInterface $container): CommandTester
    {
        return CommandHelper::getCommandTester($container, UpdateCommand::class);
    }
}
