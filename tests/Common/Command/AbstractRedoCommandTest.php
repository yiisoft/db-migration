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
use Yiisoft\Db\Migration\Command\RedoCommand;
use Yiisoft\Db\Migration\Migrator;
use Yiisoft\Db\Migration\Tests\Support\AssertTrait;
use Yiisoft\Db\Migration\Tests\Support\Helper\CommandHelper;
use Yiisoft\Db\Migration\Tests\Support\Helper\DbHelper;
use Yiisoft\Db\Migration\Tests\Support\Helper\MigrationHelper;
use Yiisoft\Db\Migration\Tests\Support\Migrations\M231017150317EmptyDown;
use Yiisoft\Db\Migration\Tests\Support\Stub\StubMigration;

abstract class AbstractRedoCommandTest extends TestCase
{
    use AssertTrait;

    protected ContainerInterface $container;

    public function testExecuteWithNamespace(): void
    {
        MigrationHelper::useMigrationsNamespace($this->container);

        MigrationHelper::createAndApplyMigration(
            $this->container,
            'Create_Post',
            'table',
            'post',
            ['name:string(50)'],
        );
        MigrationHelper::createAndApplyMigration(
            $this->container,
            'Create_User',
            'table',
            'user',
            ['name:string(50)'],
        );

        $command = $this->createCommand($this->container);
        $command->setInputs(['yes']);

        $exitCode = $command->execute([]);
        $output = $command->getDisplay(true);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('1 migration was redone.', $output);
        $this->assertStringContainsString('Migration redone successfully.', $output);
        $this->assertExistsTables($this->container, 'post', 'user');
    }

    public function testExecuteWithPath(): void
    {
        MigrationHelper::useMigrationsPath($this->container);

        MigrationHelper::createAndApplyMigration(
            $this->container,
            'Create_Post',
            'table',
            'post',
            ['name:string(50)'],
        );
        MigrationHelper::createAndApplyMigration(
            $this->container,
            'Create_User',
            'table',
            'user',
            ['name:string(50)'],
        );

        $command = $this->createCommand($this->container);
        $command->setInputs(['yes']);

        $exitCode = $command->execute([]);
        $output = $command->getDisplay(true);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('1 migration was redone.', $output);
        $this->assertStringContainsString('Migration redone successfully.', $output);
        $this->assertExistsTables($this->container, 'post', 'user');
    }

    public function testLimit(): void
    {
        MigrationHelper::useMigrationsNamespace($this->container);

        MigrationHelper::createAndApplyMigration(
            $this->container,
            'Create_Post',
            'table',
            'post',
            ['name:string(50)'],
        );
        MigrationHelper::createAndApplyMigration(
            $this->container,
            'Create_User',
            'table',
            'user',
            ['name:string(50)'],
        );

        $command = $this->createCommand($this->container);
        $command->setInputs(['yes']);

        $exitCode = $command->execute(['-l' => '1']);
        $output = $command->getDisplay(true);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Drop table user', $output);
        $this->assertStringContainsString('create table user', $output);
        $this->assertStringContainsString('1 migration was redone.', $output);
        $this->assertStringContainsString('Migration redone successfully.', $output);
        $this->assertExistsTables($this->container, 'post', 'user');
    }

    public function testIncorrectLimit(): void
    {
        MigrationHelper::useMigrationsNamespace($this->container);

        MigrationHelper::createAndApplyMigration(
            $this->container,
            'Create_Post',
            'table',
            'post',
            ['name:string(50)'],
        );

        $command = $this->createCommand($this->container);
        $command->setInputs(['yes']);

        $exitCode = $command->execute(['-l' => -1]);
        $output = $command->getDisplay(true);

        $this->assertSame(Command::INVALID, $exitCode);
        $this->assertStringContainsString('The limit option must be greater than 0.', $output);
    }

    public function testWithoutNewMigrations(): void
    {
        MigrationHelper::useMigrationsNamespace($this->container);

        $command = $this->createCommand($this->container);
        $command->setInputs(['yes']);

        $exitCode = $command->execute([]);
        $output = $command->getDisplay(true);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('[WARNING] No migration has been done before.', $output);
    }

    public function testNotRevertibleMigrationInterface(): void
    {
        MigrationHelper::useMigrationsPath($this->container);

        $this->container->get(Migrator::class)->up(new StubMigration());

        $command = $this->createCommand($this->container);
        $command->setInputs(['yes']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Migration ' . StubMigration::class . ' does not implement RevertibleMigrationInterface.'
        );
        $command->execute([]);
    }

    public function testOptionAll(): void
    {
        MigrationHelper::useMigrationsNamespace($this->container);

        MigrationHelper::createAndApplyMigration(
            $this->container,
            'Create_Post',
            'table',
            'post',
            ['name:string(50)'],
        );
        MigrationHelper::createAndApplyMigration(
            $this->container,
            'Create_User',
            'table',
            'user',
            ['name:string(50)'],
        );

        $command = $this->createCommand($this->container);

        $exitCode = $command->setInputs(['no'])->execute(['--all' => true]);
        $output = $command->getDisplay(true);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Total 2 migrations to be redone:', $output);
    }

    public function testPartiallyReverted(): void
    {
        MigrationHelper::useMigrationsNamespace($this->container);
        MigrationHelper::createAndApplyMigration(
            $this->container,
            'Create_Book',
            'table',
            'book',
            ['title:string(100)', 'author:string(80)'],
        );
        MigrationHelper::createAndApplyMigration(
            $this->container,
            'Create_Chapter',
            'table',
            'chapter',
            ['name:string(100)'],
        );

        $db = $this->container->get(ConnectionInterface::class);
        $db->createCommand()->dropTable('book')->execute();

        $command = $this->createCommand($this->container);

        try {
            $exitCode = $command->setInputs(['yes'])->execute(['-l' => 2]);
        } catch (Throwable) {
        }

        $output = $command->getDisplay(true);

        $this->assertFalse(isset($exitCode));
        $this->assertStringContainsString('>>> Total 1 out of 2 migrations were reverted.', $output);
        $this->assertStringContainsString('[ERROR] Partially reverted.', $output);
    }

    public function testNotReverted(): void
    {
        MigrationHelper::useMigrationsNamespace($this->container);
        MigrationHelper::createAndApplyMigration(
            $this->container,
            'Create_Book',
            'table',
            'book',
            ['title:string(100)', 'author:string(80)'],
        );

        $db = $this->container->get(ConnectionInterface::class);
        DbHelper::dropTable($db, 'book');

        $command = $this->createCommand($this->container);

        try {
            $exitCode = $command->setInputs(['yes'])->execute([]);
        } catch (Throwable) {
        }

        $output = $command->getDisplay(true);

        $this->assertFalse(isset($exitCode));
        $this->assertStringContainsString('>>> Total 0 out of 1 migration was reverted.', $output);
        $this->assertStringContainsString('[ERROR] Not reverted.', $output);
    }

    public function testRevertedButPartiallyApplied(): void
    {
        MigrationHelper::useMigrationsNamespace($this->container);
        $createBookClass = MigrationHelper::createAndApplyMigration(
            $this->container,
            'Create_Book',
            'table',
            'book',
            ['title:string(100)', 'author:string(80)'],
        );

        $migrator = $this->container->get(Migrator::class);
        $migrator->up(new M231017150317EmptyDown());

        $command = $this->createCommand($this->container);

        try {
            $exitCode = $command->setInputs(['yes'])->execute(['-a' => true]);
        } catch (Throwable) {
        }

        $output = $command->getDisplay(true);

        $this->assertFalse(isset($exitCode));
        $this->assertStringContainsString('>>> Total 1 out of 2 migrations were applied.', $output);
        $this->assertStringContainsString('[ERROR] Reverted but partially applied.', $output);

        $this->container->get(Migrator::class)->down(new $createBookClass());
        $db = $this->container->get(ConnectionInterface::class);
        DbHelper::dropTable($db, 'chapter');
    }

    public function testRevertedButNotApplied(): void
    {
        $migrator = $this->container->get(Migrator::class);
        $migrator->up(new M231017150317EmptyDown());

        $command = $this->createCommand($this->container);

        try {
            $exitCode = $command->setInputs(['yes'])->execute([]);
        } catch (Throwable) {
        }

        $output = $command->getDisplay(true);

        $this->assertFalse(isset($exitCode));
        $this->assertStringContainsString('>>> Total 0 out of 1 migration was applied.', $output);
        $this->assertStringContainsString('[ERROR] Reverted but not applied.', $output);

        $db = $this->container->get(ConnectionInterface::class);
        DbHelper::dropTable($db, 'chapter');
    }

    public function createCommand(ContainerInterface $container): CommandTester
    {
        return CommandHelper::getCommandTester($container, RedoCommand::class);
    }
}
