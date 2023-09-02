<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Common\Command;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Migration\Command\UpdateCommand;
use Yiisoft\Db\Migration\Service\MigrationService;
use Yiisoft\Db\Migration\Tests\Support\AssertTrait;
use Yiisoft\Db\Migration\Tests\Support\Helper\CommandHelper;
use Yiisoft\Db\Migration\Tests\Support\Helper\MigrationHelper;
use Yiisoft\Db\Migration\Tests\Support\Stub\StubMigrationInformer;

abstract class AbstractUpdateCommandTest extends TestCase
{
    use AssertTrait;

    protected ContainerInterface $container;
    private StubMigrationInformer $informer;

    public function testExecuteWithPath(): void
    {
        MigrationHelper::useMigrationsPath($this->container);

        MigrationHelper::createMigration(
            $this->container,
            'Create_Department',
            'table',
            'department',
            ['name:string(50)'],
        );

        $command = $this->createCommand($this->container);
        $command->setInputs(['yes']);

        $exitCode = $command->execute([]);
        $output = $command->getDisplay(true);

        $db = $this->container->get(ConnectionInterface::class);
        $dbSchema = $db->getSchema();
        $departmentSchema = $dbSchema->getTableSchema('department');

        $this->assertSame(Command::SUCCESS, $exitCode);

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

        // check title
        $className = MigrationHelper::findMigrationClassNameInOutput($output);

        $this->assertStringContainsString(">>> [OK] - '.Done..'.", $output);
        $this->assertStringContainsString('Total 1 new migration to be applied:', $output);
        $this->assertStringContainsString('Apply the above migration y/n:', $output);
        $this->assertStringContainsString("Applying $className", $output);
        $this->assertStringContainsString(">>> [OK] - Applied $className", $output);
        $this->assertStringContainsString('>>> 1 Migration was applied.', $output);
    }

    public function testExecuteWithNamespace(): void
    {
        MigrationHelper::useMigrationsNamespace($this->container);

        MigrationHelper::createMigration(
            $this->container,
            'Create_Department',
            'table',
            'department',
            ['name:string(50)'],
        );

        $command = $this->createCommand($this->container);
        $command->setInputs(['yes']);

        $exitCode = $command->execute([]);

        $db = $this->container->get(ConnectionInterface::class);
        $dbSchema = $db->getSchema();
        $departmentSchema = $dbSchema->getTableSchema('department');

        $this->assertSame(Command::SUCCESS, $exitCode);

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
        MigrationHelper::useMigrationsPath($this->container);

        MigrationHelper::createMigration(
            $this->container,
            'Create_Department',
            'table',
            'department',
            ['name:string(50)'],
        );

        MigrationHelper::createMigration(
            $this->container,
            'Create_Student',
            'table',
            'student',
            [
                'name:string(50):comment("Student Name")',
                'department_id:integer:notNull:foreignKey(department)',
                'dateofbirth:date',
            ],
        );

        $command = $this->createCommand($this->container);
        $command->setInputs(['yes']);

        $exitCode = $command->execute([]);
        $output = $command->getDisplay(true);

        $db = $this->container->get(ConnectionInterface::class);
        $dbSchema = $db->getSchema();
        $departmentSchema = $dbSchema->getTableSchema('department');
        $studentSchema = $dbSchema->getTableSchema('student');

        $this->assertSame(Command::SUCCESS, $exitCode);

        /** Check create table department columns*/
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Apply the above migrations y/n:', $output);
        $this->assertStringContainsString('>>> 2 Migrations were applied.', $output);

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
            $dbSchema->getTableForeignKeys('student')[0]->getColumnNames()
        );

        /** Check table student field dateofbirth */
        $this->assertSame('dateofbirth', $studentSchema->getColumn('dateofbirth')->getName());

        if ($db->getDriverName() !== 'oci') {
            $this->assertSame('date', $studentSchema->getColumn('dateofbirth')->getType());
        } else {
            $this->assertSame('string', $studentSchema->getColumn('dateofbirth')->getType());
        }

        $this->asserttrue($studentSchema->getColumn('dateofbirth')->isAllowNull());
    }

    public function testExecuteAgain(): void
    {
        MigrationHelper::useMigrationsNamespace($this->container);

        MigrationHelper::createMigration(
            $this->container,
            'Create_Department',
            'table',
            'department',
            ['name:string(50)'],
        );

        $command1 = $this->createCommand($this->container);
        $command1->setInputs(['yes']);

        $exitCode1 = $command1->execute([]);
        $output1 = $command1->getDisplay(true);

        $command2 = $this->createCommand($this->container);
        $command2->setInputs(['yes']);

        $exitCode2 = $command2->execute([]);
        $output2 = $command2->getDisplay(true);

        $this->assertSame(Command::SUCCESS, $exitCode1);
        $this->assertStringContainsString('1 Migration was applied.', $output1);

        $this->assertSame(Command::SUCCESS, $exitCode2);
        $this->assertStringContainsString('No new migrations found.', $output2);
        $this->assertStringContainsString('[OK] Your system is up-to-date.', $output2);
    }

    public function testNotMigrationInterface(): void
    {
        MigrationHelper::useMigrationsPath($this->container);

        $className = MigrationHelper::createMigration(
            $this->container,
            'Test_Not_Migration_Interface',
            'table',
            'department',
            ['name:string(50)'],
            static fn (string $content) => str_replace(
                'implements RevertibleMigrationInterface, TransactionalMigrationInterface',
                '',
                $content
            ),
        );

        $command = $this->createCommand($this->container);
        $command->setInputs(['yes']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Migration $className does not implement MigrationInterface.");
        $command->execute([]);
    }

    public function testWithoutUpdatePath(): void
    {
        MigrationHelper::useMigrationsPath($this->container);

        $this->container->get(MigrationService::class)->updatePaths([]);

        $command = $this->createCommand($this->container);
        $command->setInputs(['yes']);

        $exitCode = $command->execute([]);
        $output = $command->getDisplay(true);

        $this->assertSame(Command::INVALID, $exitCode);
        $this->assertStringContainsStringCollapsingSpaces(
            'At least one of `updateNamespaces` or `updatePaths` should be specified.',
            $output
        );
    }

    public function testWithoutUpdateNamespaces(): void
    {
        MigrationHelper::useMigrationsNamespace($this->container);

        $this->container->get(MigrationService::class)->updateNamespaces([]);

        $command = $this->createCommand($this->container);
        $command->setInputs(['yes']);

        $exitCode = $command->execute([]);
        $output = $command->getDisplay(true);

        $this->assertSame(Command::INVALID, $exitCode);
        $this->assertStringContainsStringCollapsingSpaces(
            'At least one of `updateNamespaces` or `updatePaths` should be specified.',
            $output
        );
    }

    public function testLimit(): void
    {
        MigrationHelper::useMigrationsNamespace($this->container);

        MigrationHelper::createMigration(
            $this->container,
            'Create_Post',
            'table',
            'post',
            ['name:string(50)'],
        );
        sleep(1);
        MigrationHelper::createMigration(
            $this->container,
            'Create_User',
            'table',
            'user',
            ['name:string(50)'],
        );

        $command = $this->createCommand($this->container);
        $command->setInputs(['yes']);

        $exitCode = $command->execute(['-l' => 1]);
        $output = $command->getDisplay(true);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Total 1 out of 2 new migrations to be applied:', $output);
        $this->assertStringContainsString('create table post', $output);
        $this->assertExistsTables($this->container, 'post');
        $this->assertNotExistsTables($this->container, 'user');
    }

    public function testNameLimit(): void
    {
        MigrationHelper::useMigrationsNamespace($this->container);

        MigrationHelper::createMigration(
            $this->container,
            'Create_Post' . str_repeat('X', 200),
            'table',
            'post',
            ['name:string(50)'],
        );

        $command = $this->createCommand($this->container);
        $command->setInputs(['yes']);

        $exitCode = $command->execute([]);
        $output = $command->getDisplay(true);

        $this->assertSame(Command::INVALID, $exitCode);
        $this->assertStringContainsString(
            'is too long. Its not possible to apply this migration.',
            $output
        );
    }

    public function createCommand(ContainerInterface $container): CommandTester
    {
        return CommandHelper::getCommandTester($container, UpdateCommand::class);
    }
}
