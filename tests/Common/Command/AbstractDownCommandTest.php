<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Common\Command;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionException;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Yii\Db\Migration\Command\DownCommand;
use Yiisoft\Yii\Db\Migration\Migrator;
use Yiisoft\Yii\Db\Migration\Tests\Support\AssertTrait;
use Yiisoft\Yii\Db\Migration\Tests\Support\Helper\CommandHelper;
use Yiisoft\Yii\Db\Migration\Tests\Support\Helper\MigrationHelper;
use Yiisoft\Yii\Db\Migration\Tests\Support\Stub\StubMigration;

abstract class AbstractDownCommandTest extends TestCase
{
    use AssertTrait;

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
        $this->assertStringContainsString('1 migration was reverted.', $output);
        $this->assertNotExistsTables($this->container, 'user');
        $this->assertExistsTables($this->container, 'post');
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
        $this->assertStringContainsString('1 migration was reverted.', $output);
        $this->assertNotExistsTables($this->container, 'user');
        $this->assertExistsTables($this->container, 'post');
    }

    public function testExecuteAgain(): void
    {
        MigrationHelper::useMigrationsNamespace($this->container);

        MigrationHelper::createAndApplyMigration(
            $this->container,
            'Create_Department',
            'table',
            'department',
            ['name:string(50)']
        );

        $command1 = $this->createCommand($this->container);
        $command1->setInputs(['yes']);

        $exitCode1 = $command1->execute([]);
        $output1 = $command1->getDisplay(true);

        $command2 = $this->createCommand($this->container);
        $command2->setInputs(['yes']);

        $exitCode2 = $command2->execute([]);
        $output2 = $command2->getDisplay(true);

        $this->assertSame(Command::SUCCESS, $exitCode1);
        $this->assertStringContainsString('1 migration was reverted.', $output1);

        $this->assertSame(Command::FAILURE, $exitCode2);
        $this->assertStringContainsString('[WARNING] No migration has been done before.', $output2);
    }

    public function testDowngradeAll(): void
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

        $exitCode = $command->execute(['--all' => true]);
        $output = $command->getDisplay(true);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('2 migrations were reverted.', $output);
        $this->assertNotExistsTables($this->container, 'user', 'post');
    }

    public function testFailCreateMigrationInstanceWithNamespace(): void
    {
        MigrationHelper::useMigrationsNamespace($this->container);

        $db = $this->container->get(ConnectionInterface::class);
        $migrator = $this->container->get(Migrator::class);

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

        $command = $this->createCommand($this->container);

        $this->expectException(ReflectionException::class);
        $this->expectExceptionMessageMatches('/Class ("|)\\\\Migration\\\\FakeMigration("|) does not exist$/');
        $command->execute(['']);

        $db->close();
    }

    public function testFailCreateMigrationInstanceWithPath(): void
    {
        MigrationHelper::useMigrationsPath($this->container);

        $db = $this->container->get(ConnectionInterface::class);
        $migrator = $this->container->get(Migrator::class);

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

        $command = $this->createCommand($this->container);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Migration file not found.');
        $command->execute(['']);

        $db->close();
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
            'Create_Tag',
            'table',
            'tag',
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

        $exitCode = $command->execute(['-l' => '2']);
        $output = $command->getDisplay(true);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('[OK] 2 migrations were reverted.', $output);
    }

    public static function dataIncorrectLimit(): array
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
        $command = $this->createCommand($this->container);

        $exitCode = $command->execute(['--limit' => $limit]);
        $output = $command->getDisplay(true);

        $this->assertSame(Command::INVALID, $exitCode);
        $this->assertStringContainsString('The limit argument must be greater than 0.', $output);
    }

    public function createCommand(ContainerInterface $container): CommandTester
    {
        return CommandHelper::getCommandTester($container, DownCommand::class);
    }
}
