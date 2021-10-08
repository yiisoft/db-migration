<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Command;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Db\Migration\Command\NewCommand;
use Yiisoft\Yii\Db\Migration\Tests\Support\Helper\CommandHelper;
use Yiisoft\Yii\Db\Migration\Tests\Support\Helper\MigrationHelper;
use Yiisoft\Yii\Db\Migration\Tests\Support\Helper\SqLiteHelper;

final class NewCommandTest extends TestCase
{
    public function testExecuteWithNamespace(): void
    {
        $container = SqLiteHelper::createContainer();
        MigrationHelper::useMigrationsNamespace($container);
        SqLiteHelper::clearDatabase($container);

        MigrationHelper::createAndApplyMigration(
            $container,
            'Create_Post',
            'table',
            'post',
            ['name:string(50)'],
        );

        $classCreateUser = MigrationHelper::createMigration(
            $container,
            'Create_User',
            'table',
            'user',
            ['name:string(32)'],
        );
        $classCreateTag = MigrationHelper::createMigration(
            $container,
            'Create_Tag',
            'table',
            'tag',
            ['name:string(32)'],
        );

        $command = $this->createCommand($container);

        $exitCode = $command->execute([]);
        $output = $command->getDisplay(true);

        $this->assertSame(ExitCode::OK, $exitCode);
        $this->assertStringContainsString('Found 2 new migrations:', $output);
        $this->assertStringContainsString($classCreateUser, $output);
        $this->assertStringContainsString($classCreateTag, $output);
        $this->assertSame(2, substr_count($output, MigrationHelper::NAMESPACE));
    }

    public function testExecuteWithPath(): void
    {
        $container = SqLiteHelper::createContainer();
        MigrationHelper::useMigrationsPath($container);
        SqLiteHelper::clearDatabase($container);

        MigrationHelper::createAndApplyMigration(
            $container,
            'Create_Post',
            'table',
            'post',
            ['name:string(50)'],
        );

        $classCreateUser = MigrationHelper::createMigration(
            $container,
            'Create_User',
            'table',
            'user',
            ['name:string(32)'],
        );
        $classCreateTag = MigrationHelper::createMigration(
            $container,
            'Create_Tag',
            'table',
            'tag',
            ['name:string(32)'],
        );

        $command = $this->createCommand($container);

        $exitCode = $command->execute([]);
        $output = $command->getDisplay(true);

        $this->assertSame(ExitCode::OK, $exitCode);
        $this->assertStringContainsString('Found 2 new migrations:', $output);
        $this->assertStringContainsString($classCreateUser, $output);
        $this->assertStringContainsString($classCreateTag, $output);
    }

    public function testIncorrectLimit(): void
    {
        $container = SqLiteHelper::createContainer();

        $command = $this->createCommand($container);

        $exitCode = $command->execute(['-l' => -1]);
        $output = $command->getDisplay(true);

        $this->assertSame(ExitCode::DATAERR, $exitCode);
        $this->assertStringContainsString('[ERROR] The step argument must be greater than 0.', $output);
    }

    public function testWithoutNewMigrations(): void
    {
        $container = SqLiteHelper::createContainer();
        MigrationHelper::useMigrationsPath($container);
        SqLiteHelper::clearDatabase($container);

        MigrationHelper::createAndApplyMigration(
            $container,
            'Create_Post',
            'table',
            'post',
            ['name:string(50)'],
        );

        $command = $this->createCommand($container);

        $exitCode = $command->execute([]);
        $output = $command->getDisplay(true);

        $this->assertSame(ExitCode::UNSPECIFIED_ERROR, $exitCode);
        $this->assertStringContainsString('[OK] No new migrations found. Your system is up-to-date.', $output);
    }

    public function testCountMigrationsMoreLimit(): void
    {
        $container = SqLiteHelper::createContainer();
        MigrationHelper::useMigrationsPath($container);
        SqLiteHelper::clearDatabase($container);

        $classCreatePost = MigrationHelper::createMigration(
            $container,
            'Create_Post',
            'table',
            'post',
            ['name:string(50)'],
        );
        sleep(1);
        $classCreateUser = MigrationHelper::createMigration(
            $container,
            'Create_User',
            'table',
            'user',
            ['name:string(32)'],
        );
        sleep(1);
        MigrationHelper::createMigration(
            $container,
            'Create_Tag',
            'table',
            'tag',
            ['name:string(32)'],
        );

        $command = $this->createCommand($container);

        $exitCode = $command->execute(['--limit' => 2]);
        $output = $command->getDisplay(true);

        $this->assertSame(ExitCode::OK, $exitCode);
        $this->assertStringContainsString('[WARNING] Showing 2 out of 3 new migrations:', $output);
        $this->assertStringContainsString($classCreatePost, $output);
        $this->assertStringContainsString($classCreateUser, $output);
    }

    public function createCommand(ContainerInterface $container): CommandTester
    {
        return CommandHelper::getCommandTester($container, NewCommand::class);
    }
}
