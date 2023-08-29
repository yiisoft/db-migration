<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Common\Command;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Yii\Db\Migration\Command\HistoryCommand;
use Yiisoft\Yii\Db\Migration\Tests\Support\Helper\CommandHelper;
use Yiisoft\Yii\Db\Migration\Tests\Support\Helper\MigrationHelper;

abstract class AbstractHistoryCommandTest extends TestCase
{
    protected ContainerInterface $container;
    protected string $driverName = '';

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
        $output = $command->getDisplay(true);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Total 2 migrations have been applied before:', $output);
        $this->assertStringContainsString(date('y-m-d'), $output);
        $this->assertStringContainsString($classPost, $output);
        $this->assertStringContainsString($classTag, $output);
        $this->assertStringContainsString(' [OK] Success.', $output);
        $this->assertStringContainsString('Database connection: ' . $this->driverName, $output);
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
        $output = $command->getDisplay(true);

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
        $output = $command->getDisplay(true);

        $this->assertSame(Command::INVALID, $exitCode);
        $this->assertStringContainsString('The step argument must be greater than 0.', $output);
        $this->assertStringContainsString('Database connection: ' . $this->driverName, $output);
    }

    public function testWithoutNewMigrations(): void
    {
        MigrationHelper::useMigrationsNamespace($this->container);

        $command = $this->createCommand($this->container);

        $exitCode = $command->execute([]);
        $output = $command->getDisplay(true);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('[WARNING] No migration has been done before.', $output);
    }

    public function createCommand(ContainerInterface $container): CommandTester
    {
        return CommandHelper::getCommandTester($container, HistoryCommand::class);
    }
}
