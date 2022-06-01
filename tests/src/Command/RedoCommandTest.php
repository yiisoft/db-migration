<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Command;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Db\Migration\Command\RedoCommand;
use Yiisoft\Yii\Db\Migration\Migrator;
use Yiisoft\Yii\Db\Migration\Tests\Support\AssertTrait;
use Yiisoft\Yii\Db\Migration\Tests\Support\Helper\CommandHelper;
use Yiisoft\Yii\Db\Migration\Tests\Support\Helper\MigrationHelper;
use Yiisoft\Yii\Db\Migration\Tests\Support\Helper\SqLiteHelper;
use Yiisoft\Yii\Db\Migration\Tests\Support\Stub\StubMigration;

final class RedoCommandTest extends TestCase
{
    use AssertTrait;

    public function testExecuteWithNamespace(): void
    {
        $container = SqLiteHelper::createContainer();
        SqLiteHelper::clearDatabase($container);
        MigrationHelper::useMigrationsNamespace($container);

        MigrationHelper::createAndApplyMigration(
            $container,
            'Create_Post',
            'table',
            'post',
            ['name:string(50)'],
        );
        MigrationHelper::createAndApplyMigration(
            $container,
            'Create_User',
            'table',
            'user',
            ['name:string(50)'],
        );

        $command = $this->createCommand($container);
        $command->setInputs(['yes']);

        $exitCode = $command->execute([]);
        $output = $command->getDisplay(true);

        $this->assertSame(ExitCode::OK, $exitCode);
        $this->assertStringContainsString('2 migrations were redone.', $output);
        $this->assertStringContainsString('Migration redone successfully.', $output);
        $this->assertExistsTables($container, 'post', 'user');
    }

    public function testExecuteWithPath(): void
    {
        $container = SqLiteHelper::createContainer();
        SqLiteHelper::clearDatabase($container);
        MigrationHelper::useMigrationsPath($container);

        MigrationHelper::createAndApplyMigration(
            $container,
            'Create_Post',
            'table',
            'post',
            ['name:string(50)'],
        );
        MigrationHelper::createAndApplyMigration(
            $container,
            'Create_User',
            'table',
            'user',
            ['name:string(50)'],
        );

        $command = $this->createCommand($container);
        $command->setInputs(['yes']);

        $exitCode = $command->execute([]);
        $output = $command->getDisplay(true);

        $this->assertSame(ExitCode::OK, $exitCode);
        $this->assertStringContainsString('2 migrations were redone.', $output);
        $this->assertStringContainsString('Migration redone successfully.', $output);
        $this->assertExistsTables($container, 'post', 'user');
    }

    public function testLimit(): void
    {
        $container = SqLiteHelper::createContainer();
        SqLiteHelper::clearDatabase($container);
        MigrationHelper::useMigrationsNamespace($container);

        MigrationHelper::createAndApplyMigration(
            $container,
            'Create_Post',
            'table',
            'post',
            ['name:string(50)'],
        );
        MigrationHelper::createAndApplyMigration(
            $container,
            'Create_User',
            'table',
            'user',
            ['name:string(50)'],
        );

        $command = $this->createCommand($container);
        $command->setInputs(['yes']);

        $exitCode = $command->execute(['-l' => '1']);
        $output = $command->getDisplay(true);

        $this->assertSame(ExitCode::OK, $exitCode);
        $this->assertStringContainsString('Drop table user', $output);
        $this->assertStringContainsString('create table user', $output);
        $this->assertStringContainsString('1 migration was redone.', $output);
        $this->assertStringContainsString('Migration redone successfully.', $output);
        $this->assertExistsTables($container, 'post', 'user');
    }

    public function testIncorrectLimit(): void
    {
        $container = SqLiteHelper::createContainer();
        SqLiteHelper::clearDatabase($container);
        MigrationHelper::useMigrationsNamespace($container);

        MigrationHelper::createAndApplyMigration(
            $container,
            'Create_Post',
            'table',
            'post',
            ['name:string(50)'],
        );

        $command = $this->createCommand($container);
        $command->setInputs(['yes']);

        $exitCode = $command->execute(['-l' => -1]);
        $output = $command->getDisplay(true);

        $this->assertSame(ExitCode::DATAERR, $exitCode);
        $this->assertStringContainsString('The step argument must be greater than 0.', $output);
    }

    public function testWithoutNewMigrations(): void
    {
        $container = SqLiteHelper::createContainer();
        SqLiteHelper::clearDatabase($container);
        MigrationHelper::useMigrationsNamespace($container);

        $command = $this->createCommand($container);
        $command->setInputs(['yes']);

        $exitCode = $command->execute([]);
        $output = $command->getDisplay(true);

        $this->assertSame(ExitCode::UNSPECIFIED_ERROR, $exitCode);
        $this->assertStringContainsString('No migration has been done before.', $output);
    }

    public function testNotRevertibleMigrationInterface(): void
    {
        $container = SqLiteHelper::createContainer();
        SqLiteHelper::clearDatabase($container);
        MigrationHelper::useMigrationsPath($container);

        $container
            ->get(Migrator::class)
            ->up(new StubMigration());

        $command = $this->createCommand($container);
        $command->setInputs(['yes']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Migration ' . StubMigration::class . ' does not implement RevertibleMigrationInterface.'
        );
        $command->execute([]);
    }

    public function createCommand(ContainerInterface $container): CommandTester
    {
        return CommandHelper::getCommandTester($container, RedoCommand::class);
    }
}
