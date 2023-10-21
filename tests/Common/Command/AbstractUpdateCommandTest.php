<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Common\Command;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Throwable;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Migration\Command\UpdateCommand;
use Yiisoft\Db\Migration\Migrator;
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
        $this->assertStringContainsString("1. $className", $output);
        $this->assertStringContainsString('Apply the above migration y/n:', $output);
        $this->assertStringContainsString("1. Applying $className", $output);
        $this->assertStringContainsString('>>> [OK] - Applied (time:', $output);
        $this->assertStringContainsString('>>> Total 1 new migration was applied.', $output);
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
        $db = $this->container->get(ConnectionInterface::class);

        if ($db->getDriverName() === 'sqlite') {
            self::markTestSkipped('Skipped due to issues #218 and #219.');
        }

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

        $dbSchema = $db->getSchema();
        $departmentSchema = $dbSchema->getTableSchema('department');
        $studentSchema = $dbSchema->getTableSchema('student');

        /** Check create table department columns*/
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Apply the above migrations y/n:', $output);
        $this->assertStringContainsString('>>> Total 2 new migrations were applied.', $output);

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
        $this->assertStringContainsString('Total 1 new migration was applied.', $output1);

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

    public function testOptionPath(): void
    {
        MigrationHelper::useMigrationsPath($this->container);
        $classCreateBook = MigrationHelper::createMigration(
            $this->container,
            'Create_Book',
            'table',
            'book',
            ['title:string(100)', 'author:string(80)'],
        );
        MigrationHelper::resetPathAndNamespace($this->container);

        $command = $this->createCommand($this->container);

        $exitCode = $command->setInputs(['no'])->execute(['--path' => [MigrationHelper::PATH_ALIAS]]);
        $output = $command->getDisplay(true);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Total 1 new migration to be applied:', $output);
        $this->assertStringContainsString($classCreateBook, $output);
    }

    public function testOptionNamespace(): void
    {
        MigrationHelper::useMigrationsNamespace($this->container);
        $classCreateChapter = MigrationHelper::createMigration(
            $this->container,
            'Create_Chapter',
            'table',
            'chapter',
            ['name:string(100)'],
        );
        MigrationHelper::resetPathAndNamespace($this->container);

        $command = $this->createCommand($this->container);

        foreach (['--namespace', '-ns'] as $option) {
            $exitCode = $command->setInputs(['no'])->execute([$option => [MigrationHelper::NAMESPACE]]);
            $output = $command->getDisplay(true);

            $this->assertSame(Command::SUCCESS, $exitCode);
            $this->assertStringContainsString('Total 1 new migration to be applied:', $output);
            $this->assertStringContainsString($classCreateChapter, $output);
        }
    }

    public function testIncorrectLimit(): void
    {
        MigrationHelper::useMigrationsNamespace($this->container);

        $command = $this->createCommand($this->container);

        $exitCode = $command->execute(['-l' => -1]);
        $output = $command->getDisplay(true);

        $this->assertSame(Command::INVALID, $exitCode);
        $this->assertStringContainsString('[ERROR] The limit option must be greater than 0.', $output);
    }

    public function testPartiallyUpdated(): void
    {
        MigrationHelper::useMigrationsNamespace($this->container);
        $createBookClass = MigrationHelper::createMigration(
            $this->container,
            '1Create_Book',
            'table',
            'book',
            ['title:string(100)', 'author:string(80)'],
        );
        MigrationHelper::createMigration(
            $this->container,
            '2Create_Book',
            'table',
            'book',
            ['title:string(100)', 'author:string(80)'],
        );

        $command = $this->createCommand($this->container);

        try {
            $exitCode = $command->setInputs(['yes'])->execute([]);
        } catch (Throwable) {
        }

        $output = $command->getDisplay(true);

        $this->assertFalse(isset($exitCode));
        $this->assertStringContainsString('>>> Total 1 out of 2 new migrations were applied.', $output);
        $this->assertStringContainsString('[ERROR] Partially updated.', $output);

        $this->container->get(Migrator::class)->down(new $createBookClass());
    }

    public function testNotUpdated(): void
    {
        MigrationHelper::useMigrationsNamespace($this->container);
        $createBookClass = MigrationHelper::createAndApplyMigration(
            $this->container,
            '1Create_Book',
            'table',
            'book',
            ['title:string(100)', 'author:string(80)'],
        );
        MigrationHelper::createMigration(
            $this->container,
            '2Create_Book',
            'table',
            'book',
            ['title:string(100)', 'author:string(80)'],
        );

        $command = $this->createCommand($this->container);

        try {
            $exitCode = $command->setInputs(['yes'])->execute([]);
        } catch (Throwable) {
        }

        $output = $command->getDisplay(true);

        $this->assertFalse(isset($exitCode));
        $this->assertStringContainsString('>>> Total 0 out of 1 new migration was applied.', $output);
        $this->assertStringContainsString('[ERROR] Not updated.', $output);

        $this->container->get(Migrator::class)->down(new $createBookClass());
    }

    public function createCommand(ContainerInterface $container): CommandTester
    {
        return CommandHelper::getCommandTester($container, UpdateCommand::class);
    }
}
