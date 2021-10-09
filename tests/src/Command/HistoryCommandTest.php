<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Command;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Db\Migration\Command\HistoryCommand;
use Yiisoft\Yii\Db\Migration\Tests\Support\Helper\CommandHelper;
use Yiisoft\Yii\Db\Migration\Tests\Support\Helper\MigrationHelper;
use Yiisoft\Yii\Db\Migration\Tests\Support\Helper\SqLiteHelper;

final class HistoryCommandTest extends TestCase
{
    public function testExecute(): void
    {
        $container = SqLiteHelper::createContainer();
        SqLiteHelper::clearDatabase($container);
        MigrationHelper::useMigrationsNamespace($container);

        $classPost = MigrationHelper::createAndApplyMigration(
            $container,
            'Create_Post',
            'table',
            'post',
            ['name:string(50)'],
        );
        $classTag = MigrationHelper::createAndApplyMigration(
            $container,
            'Create_Tag',
            'table',
            'tag',
            ['name:string(50)'],
        );

        $command = $this->createCommand($container);

        $exitCode = $command->execute([]);
        $output = $command->getDisplay(true);

        $this->assertEquals(ExitCode::OK, $exitCode);
        $this->assertStringContainsString('Total 2 migrations have been applied before:', $output);
        $this->assertStringContainsString($classPost, $output);
        $this->assertStringContainsString($classTag, $output);
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
        $classTag = MigrationHelper::createAndApplyMigration(
            $container,
            'Create_Tag',
            'table',
            'tag',
            ['name:string(50)'],
        );

        $command = $this->createCommand($container);

        $exitCode = $command->execute(['-l' => '1']);
        $output = $command->getDisplay(true);

        $this->assertSame(ExitCode::OK, $exitCode);
        $this->assertStringContainsString('Last 1 applied migration:', $output);
        $this->assertStringContainsString($classTag, $output);
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

        $exitCode = $command->execute([]);
        $output = $command->getDisplay(true);

        $this->assertSame(ExitCode::UNSPECIFIED_ERROR, $exitCode);
        $this->assertStringContainsString('No migration has been done before.', $output);
    }

    public function createCommand(ContainerInterface $container): CommandTester
    {
        return CommandHelper::getCommandTester($container, HistoryCommand::class);
    }
}
