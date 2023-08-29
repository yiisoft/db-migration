<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Common\Command;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Yii\Db\Migration\Command\NewCommand;
use Yiisoft\Yii\Db\Migration\Tests\Support\Helper\CommandHelper;
use Yiisoft\Yii\Db\Migration\Tests\Support\Helper\MigrationHelper;

abstract class AbstractNewCommandTest extends TestCase
{
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

        $classCreateUser = MigrationHelper::createMigration(
            $this->container,
            'Create_User',
            'table',
            'user',
            ['name:string(32)'],
        );
        $classCreateTag = MigrationHelper::createMigration(
            $this->container,
            'Create_Tag',
            'table',
            'tag',
            ['name:string(32)'],
        );

        $command = $this->createCommand($this->container);

        $exitCode = $command->execute([]);
        $output = $command->getDisplay(true);
        $db = $this->container->get(ConnectionInterface::class);
        $driverName = $db->getDriverName();

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Found 2 new migrations:', $output);
        $this->assertStringContainsString($classCreateUser, $output);
        $this->assertStringContainsString($classCreateTag, $output);
        $this->assertSame(2, substr_count($output, MigrationHelper::NAMESPACE));
        $this->assertStringContainsString('Database connection: ' . $driverName, $output);
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

        $classCreateUser = MigrationHelper::createMigration(
            $this->container,
            'Create_User',
            'table',
            'user',
            ['name:string(32)'],
        );
        $classCreateTag = MigrationHelper::createMigration(
            $this->container,
            'Create_Tag',
            'table',
            'tag',
            ['name:string(32)'],
        );

        $command = $this->createCommand($this->container);

        $exitCode = $command->execute([]);
        $output = $command->getDisplay(true);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Found 2 new migrations:', $output);
        $this->assertStringContainsString($classCreateUser, $output);
        $this->assertStringContainsString($classCreateTag, $output);
    }

    public function testIncorrectLimit(): void
    {
        $command = $this->createCommand($this->container);

        $exitCode = $command->execute(['-l' => -1]);
        $output = $command->getDisplay(true);
        $db = $this->container->get(ConnectionInterface::class);
        $driverName = $db->getDriverName();

        $this->assertSame(Command::INVALID, $exitCode);
        $this->assertStringContainsString('[ERROR] The step argument must be greater than 0.', $output);
        $this->assertStringContainsString('Database connection: ' . $driverName, $output);
    }

    public function testWithoutNewMigrations(): void
    {
        MigrationHelper::useMigrationsPath($this->container);

        MigrationHelper::createAndApplyMigration(
            $this->container,
            'Create_Post',
            'table',
            'post',
            ['name:string(50)'],
        );

        $command = $this->createCommand($this->container);

        $exitCode = $command->execute([]);
        $output = $command->getDisplay(true);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('[WARNING] No new migrations found. Your system is up-to-date', $output);
    }

    public function testCountMigrationsMoreLimit(): void
    {
        MigrationHelper::useMigrationsPath($this->container);

        $classCreatePost = MigrationHelper::createMigration(
            $this->container,
            'Create_Post',
            'table',
            'post',
            ['name:string(50)'],
        );
        sleep(1);
        $classCreateUser = MigrationHelper::createMigration(
            $this->container,
            'Create_User',
            'table',
            'user',
            ['name:string(32)'],
        );
        sleep(1);
        MigrationHelper::createMigration(
            $this->container,
            'Create_Tag',
            'table',
            'tag',
            ['name:string(32)'],
        );

        $command = $this->createCommand($this->container);

        $exitCode = $command->execute(['--limit' => 2]);
        $output = $command->getDisplay(true);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('[WARNING] Showing 2 out of 3 new migrations:', $output);
        $this->assertStringContainsString($classCreatePost, $output);
        $this->assertStringContainsString($classCreateUser, $output);
    }

    public function createCommand(ContainerInterface $container): CommandTester
    {
        return CommandHelper::getCommandTester($container, NewCommand::class);
    }
}
