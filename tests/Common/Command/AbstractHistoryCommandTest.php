<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Common\Command;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Db\Migration\Command\HistoryCommand;
use Yiisoft\Db\Migration\Tests\Support\Helper\CommandHelper;
use Yiisoft\Db\Migration\Tests\Support\Helper\MigrationHelper;

abstract class AbstractHistoryCommandTest extends TestCase
{
    protected ContainerInterface $container;

    public function testExecute(): void
    {
        MigrationHelper::useMigrationsNamespace($this->container);

        $classPost = MigrationHelper::createAndApplyMigration(
            $this->container,
            'Create_Post',
            'table',
            'post',
            ['name:string(50)'],
        );
        $classTag = MigrationHelper::createAndApplyMigration(
            $this->container,
            'Create_Tag',
            'table',
            'tag',
            ['name:string(50)'],
        );

        $command = $this->createCommand($this->container);

        $exitCode = $command->execute([]);
        $output = preg_replace('/(\R|\s)+/', ' ', $command->getDisplay(true));

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Total 2 migrations have been applied before:', $output);
        $this->assertStringContainsString($classPost, $output);
        $this->assertStringContainsString($classTag, $output);
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
        $classTag = MigrationHelper::createAndApplyMigration(
            $this->container,
            'Create_Tag',
            'table',
            'tag',
            ['name:string(50)'],
        );

        $command = $this->createCommand($this->container);

        $exitCode = $command->execute(['-l' => '1']);
        $output = preg_replace('/(\R|\s)+/', ' ', $command->getDisplay(true));

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Last 1 applied migration:', $output);
        $this->assertStringContainsString($classTag, $output);
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

        $exitCode = $command->execute(['-l' => -1]);
        $output = preg_replace('/(\R|\s)+/', ' ', $command->getDisplay(true));

        $this->assertSame(Command::INVALID, $exitCode);
        $this->assertStringContainsString('The limit option must be greater than 0.', $output);
    }

    public function testWithoutNewMigrations(): void
    {
        MigrationHelper::useMigrationsNamespace($this->container);

        $command = $this->createCommand($this->container);

        $exitCode = $command->execute([]);
        $output = preg_replace('/(\R|\s)+/', ' ', $command->getDisplay(true));

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('[WARNING] No migration has been done before.', $output);
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

        $exitCode = $command->execute(['--all' => true]);
        $output = preg_replace('/(\R|\s)+/', ' ', $command->getDisplay(true));

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Total 2 migrations have been applied before:', $output);
    }

    public function createCommand(ContainerInterface $container): CommandTester
    {
        return CommandHelper::getCommandTester($container, HistoryCommand::class);
    }
}
