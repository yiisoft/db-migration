<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Common\Command;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Db\Migration\Command\NewCommand;
use Yiisoft\Db\Migration\Service\MigrationService;
use Yiisoft\Db\Migration\Tests\Support\Helper\CommandHelper;
use Yiisoft\Db\Migration\Tests\Support\Helper\MigrationHelper;
use Yiisoft\Files\FileHelper;

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
        $output = $command->getDisplay(true);

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

        $this->assertSame(Command::INVALID, $exitCode);
        $this->assertStringContainsString('[ERROR] The step argument must be greater than 0.', $output);
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
        $output = $command->getDisplay(true);

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

    public function testEmptyPath(): void
    {
        $command = $this->createCommand($this->container);

        $this->expectException(InvalidOptionException::class);
        $this->expectExceptionMessage('The "--path" option requires a value.');
        $command->execute(['--path' => null]);
    }

    public function testEmptyNamespace(): void
    {
        $command = $this->createCommand($this->container);

        $this->expectException(InvalidOptionException::class);
        $this->expectExceptionMessage('The "--namespace" option requires a value.');
        $command->execute(['--namespace' => null]);

        $this->expectException(InvalidOptionException::class);
        $this->expectExceptionMessage('The "-ns" option requires a value.');
        $command->execute(['-ns' => null]);
    }

    public function testNotExistsPath(): void
    {
        $command = $this->createCommand($this->container);

        $exitCode = $command->execute(['--path' => ['not-exists-path']]);
        $output = $command->getDisplay(true);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('[WARNING] No new migrations found. Your system is up-to-date.', $output);
        $this->assertStringContainsString('Database connection: ' . $this->driverName, $output);
    }

    public function testNotExistsNamespace(): void
    {
        $command = $this->createCommand($this->container);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid path alias: @not-exists-namespace');

        $command->execute(['--namespace' => ['not-exists-namespace']]);
        $command->execute(['-ns' => ['not-exists-namespace']]);
    }

    public function testOptionPath(): void
    {
        $command = $this->createCommand($this->container);

        $path = '@runtime/new-migration-path';
        $service = $this->container->get(MigrationService::class);
        $service->createPath($path);
        $path = $service->findMigrationPath(null);

        is_dir($path)
            ? FileHelper::clearDirectory($path)
            : mkdir($path);

        $classCreatePost = MigrationHelper::createMigration(
            $this->container,
            'Create_Book',
            'table',
            'book',
            ['title:string(100)', 'author:string(80)'],
        );

        $exitCode = $command->execute(['--path' => [$path]]);
        $output = $command->getDisplay(true);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Found 1 new migration:', $output);
        $this->assertStringContainsString('CreateBook', $output);
    }

    public function testOptionNamespace(): void
    {
        $command = $this->createCommand($this->container);

        $namespace = 'Yiisoft\\Db\\Migration\\Tests\\runtime\\NewMigrationNamespace';
        $service = $this->container->get(MigrationService::class);
        $service->createNamespace($namespace);
        $path = $service->findMigrationPath($namespace);

        is_dir($path)
            ? FileHelper::clearDirectory($path)
            : mkdir($path);

        $classCreatePost = MigrationHelper::createMigration(
            $this->container,
            'Create_Book',
            'table',
            'book',
            ['title:string(100)', 'author:string(80)'],
        );

        $exitCode = $command->execute(['--namespace' => [$namespace]]);
        $output = $command->getDisplay(true);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Found 1 new migration:', $output);
        $this->assertStringContainsString('CreateBook', $output);
    }
}
