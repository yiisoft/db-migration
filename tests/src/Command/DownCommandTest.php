<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Command;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionException;
use RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Db\Migration\Command\DownCommand;
use Yiisoft\Yii\Db\Migration\Migrator;
use Yiisoft\Yii\Db\Migration\Tests\Support\AssertTrait;
use Yiisoft\Yii\Db\Migration\Tests\Support\Helper\CommandHelper;
use Yiisoft\Yii\Db\Migration\Tests\Support\Helper\MigrationHelper;
use Yiisoft\Yii\Db\Migration\Tests\Support\Helper\SqLiteHelper;
use Yiisoft\Yii\Db\Migration\Tests\Support\Stub\StubMigration;

final class DownCommandTest extends TestCase
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
        $this->assertStringContainsString('1 migration was reverted.', $output);
        $this->assertNotExistsTables($container, 'user');
        $this->assertExistsTables($container, 'post');
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
        $this->assertStringContainsString('1 migration was reverted.', $output);
        $this->assertNotExistsTables($container, 'user');
        $this->assertExistsTables($container, 'post');
    }

    public function testExecuteAgain(): void
    {
        $container = SqLiteHelper::createContainer();
        MigrationHelper::useMigrationsNamespace($container);
        SqLiteHelper::clearDatabase($container);

        MigrationHelper::createAndApplyMigration(
            $container,
            'Create_Department',
            'table',
            'department',
            ['name:string(50)']
        );

        $command1 = $this->createCommand($container);
        $command1->setInputs(['yes']);

        $exitCode1 = $command1->execute([]);
        $output1 = $command1->getDisplay(true);

        $command2 = $this->createCommand($container);
        $command2->setInputs(['yes']);

        $exitCode2 = $command2->execute([]);
        $output2 = $command2->getDisplay(true);

        $this->assertSame(ExitCode::OK, $exitCode1);
        $this->assertStringContainsString('1 migration was reverted.', $output1);

        $this->assertSame(ExitCode::UNSPECIFIED_ERROR, $exitCode2);
        $this->assertStringContainsString('No migration has been done before.', $output2);
    }

    public function testDowngradeAll(): void
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

        $exitCode = $command->execute(['--all' => true]);
        $output = $command->getDisplay(true);

        $this->assertSame(ExitCode::OK, $exitCode);
        $this->assertStringContainsString('2 migrations were reverted.', $output);
        $this->assertNotExistsTables($container, 'user', 'post');
    }

    public function testFailCreateMigrationInstanceWithNamespace(): void
    {
        $container = SqLiteHelper::createContainer();
        SqLiteHelper::clearDatabase($container);
        MigrationHelper::useMigrationsNamespace($container);

        $db = $container->get(ConnectionInterface::class);
        $migrator = $container->get(Migrator::class);

        // For create migrations history table
        $migrator->up(new StubMigration());

        // Add fake migration to history table
        $db
            ->createCommand()
            ->insert(
                $migrator->getHistoryTable(),
                ['name' => 'Migration\FakeMigration', 'apply_time' => time() + 100],
            )
            ->execute();

        $command = $this->createCommand($container);

        $this->expectException(ReflectionException::class);
        $this->expectExceptionMessageMatches('/Class ("|)\\\\Migration\\\\FakeMigration("|) does not exist$/');
        $command->execute(['']);
    }

    public function testFailCreateMigrationInstanceWithPath(): void
    {
        $container = SqLiteHelper::createContainer();
        SqLiteHelper::clearDatabase($container);
        MigrationHelper::useMigrationsPath($container);

        $db = $container->get(ConnectionInterface::class);
        $migrator = $container->get(Migrator::class);

        // For create migrations history table
        $migrator->up(new StubMigration());

        // Add fake migration to history table
        $db
            ->createCommand()
            ->insert(
                $migrator->getHistoryTable(),
                ['name' => 'FakeMigration', 'apply_time' => time() + 100],
            )
            ->execute();

        $command = $this->createCommand($container);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Migration file not found.');
        $command->execute(['']);
    }

    public function testNotRevertibleMigrationInterface(): void
    {
        $container = SqLiteHelper::createContainer();
        SqLiteHelper::clearDatabase($container);
        MigrationHelper::useMigrationsPath($container);

        $container->get(Migrator::class)->up(new StubMigration());

        $command = $this->createCommand($container);
        $command->setInputs(['yes']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Migration ' . StubMigration::class . ' does not implement RevertibleMigrationInterface.'
        );
        $command->execute([]);
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
            'Create_Tag',
            'table',
            'tag',
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

        $exitCode = $command->execute(['-l' => '2']);
        $output = $command->getDisplay(true);

        $this->assertSame(ExitCode::OK, $exitCode);
        $this->assertStringContainsString('[OK] 2 migrations were reverted.', $output);
    }

    public function dataIncorrectLimit(): array
    {
        return [
            'negative' => [-1],
            'zero' => [0],
        ];
    }

    /**
     * @dataProvider dataIncorrectLimit
     */
    public function testIncorrectLimit(int $limit): void
    {
        $container = SqLiteHelper::createContainer();

        $command = $this->createCommand($container);

        $exitCode = $command->execute(['--limit' => $limit]);
        $output = $command->getDisplay(true);

        $this->assertSame(ExitCode::DATAERR, $exitCode);
        $this->assertStringContainsString('The limit argument must be greater than 0.', $output);
    }

    public function createCommand(ContainerInterface $container): CommandTester
    {
        return CommandHelper::getCommandTester($container, DownCommand::class);
    }
}
