<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Common\Command;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Db\Migration\Command\NewCommand;
use Yiisoft\Db\Migration\Tests\Support\Helper\CommandHelper;
use Yiisoft\Db\Migration\Tests\Support\Helper\MigrationHelper;

abstract class AbstractNewCommandTest extends TestCase
{
    protected ContainerInterface $container;
    protected string $driverName = '';

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
        $output = preg_replace('/(\R|\s)+/', ' ', $command->getDisplay(true));

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Found 2 new migrations:', $output);
        $this->assertStringContainsString($classCreateUser, $output);
        $this->assertStringContainsString($classCreateTag, $output);
        $this->assertSame(2, substr_count($output, MigrationHelper::NAMESPACE));
        $this->assertStringContainsString('Database connection: ' . $this->driverName, $output);
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
        $output = preg_replace('/(\R|\s)+/', ' ', $command->getDisplay(true));

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Found 2 new migrations:', $output);
        $this->assertStringContainsString($classCreateUser, $output);
        $this->assertStringContainsString($classCreateTag, $output);
    }

    public function testIncorrectLimit(): void
    {
        $command = $this->createCommand($this->container);

        $exitCode = $command->execute(['-l' => -1]);
        $output = preg_replace('/(\R|\s)+/', ' ', $command->getDisplay(true));

        $this->assertSame(Command::INVALID, $exitCode);
        $this->assertStringContainsString('[ERROR] The limit option must be greater than 0.', $output);
        $this->assertStringContainsString('Database connection: ' . $this->driverName, $output);
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
        $output = preg_replace('/(\R|\s)+/', ' ', $command->getDisplay(true));

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('[WARNING] No new migrations found. Your system is up-to-date', $output);
        $this->assertStringContainsString('Database connection: ' . $this->driverName, $output);
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
        $output = preg_replace('/(\R|\s)+/', ' ', $command->getDisplay(true));

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('[WARNING] Showing 2 out of 3 new migrations:', $output);
        $this->assertStringContainsString($classCreatePost, $output);
        $this->assertStringContainsString($classCreateUser, $output);
    }

    public function testOptionAll(): void
    {
        MigrationHelper::useMigrationsNamespace($this->container);

        MigrationHelper::createMigration(
            $this->container,
            'Create_Post',
            'table',
            'post',
            ['name:string(50)'],
        );
        MigrationHelper::createMigration(
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
        $this->assertStringContainsString('Found 2 new migrations:', $output);
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

        $exitCode = $command->execute(['--path' => [MigrationHelper::getRuntimePath()]]);
        $output = preg_replace('/(\R|\s)+/', ' ', $command->getDisplay(true));

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Found 1 new migration:', $output);
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
            $exitCode = $command->execute([$option => [MigrationHelper::NAMESPACE]]);
            $output = preg_replace('/(\R|\s)+/', ' ', $command->getDisplay(true));

            $this->assertSame(Command::SUCCESS, $exitCode);
            $this->assertStringContainsString('Found 1 new migration:', $output);
            $this->assertStringContainsString($classCreateChapter, $output);
        }
    }

    public function createCommand(ContainerInterface $container): CommandTester
    {
        return CommandHelper::getCommandTester($container, NewCommand::class);
    }
}
