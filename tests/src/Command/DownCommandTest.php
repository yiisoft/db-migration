<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Command;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionException;
use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Yii\Db\Migration\Command\DownCommand;
use Yiisoft\Yii\Db\Migration\Migrator;
use Yiisoft\Yii\Db\Migration\Tests\Support\CommandHelper;
use Yiisoft\Yii\Db\Migration\Tests\Support\MigrationHelper;
use Yiisoft\Yii\Db\Migration\Tests\Support\SqLiteHelper;
use Yiisoft\Yii\Db\Migration\Tests\Support\StubMigration;

final class DownCommandTest extends TestCase
{
    public function testFailCreateMigrationInstance(): void
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
        $this->expectExceptionMessage('Class "\Migration\FakeMigration" does not exist');
        $command->execute(['']);
    }

    public function createCommand(ContainerInterface $container): CommandTester
    {
        return CommandHelper::getCommandTester($container, DownCommand::class);
    }
}
