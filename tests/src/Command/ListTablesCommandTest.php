<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Command;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Db\Migration\Command\ListTablesCommand;
use Yiisoft\Yii\Db\Migration\Migrator;
use Yiisoft\Yii\Db\Migration\Service\Database\ListTablesService;
use Yiisoft\Yii\Db\Migration\Service\MigrationService;
use Yiisoft\Yii\Db\Migration\Tests\Support\Helper\DbHelper;
use Yiisoft\Yii\Db\Migration\Tests\Support\Helper\PostgreSqlHelper;
use Yiisoft\Yii\Db\Migration\Tests\Support\Helper\SqLiteHelper;

final class ListTablesCommandTest extends TestCase
{
    public function testExecute(): void
    {
        $container = SqLiteHelper::createContainer();

        SqLiteHelper::clearDatabase($container);
        DbHelper::createTable($container, 'the_post', ['name' => 'string']);
        DbHelper::createTable($container, 'the_user', ['name' => 'string']);

        $command = $this->getCommand($container);

        $exitCode = $command->execute([]);
        $output = $command->getDisplay(true);

        $this->assertSame(ExitCode::OK, $exitCode);
        $this->assertStringContainsString('List of tables for database: testdb', $output);
        $this->assertStringContainsString('the_post', $output);
        $this->assertStringContainsString('the_user', $output);
    }

    public function testSeveralSchemas(): void
    {
        $container = PostgreSqlHelper::createContainer();

        PostgreSqlHelper::clearDatabase($container);
        PostgreSqlHelper::createSchema($container, 'backup');
        DbHelper::createTable($container, 'public.test1', ['name' => 'string']);
        DbHelper::createTable($container, 'backup.test2', ['name' => 'string']);

        $command = $this->getCommand($container);

        $exitCode = $command->execute([]);
        $output = $command->getDisplay(true);

        $this->assertSame(ExitCode::OK, $exitCode);
        $this->assertStringContainsString('List of tables for database: testdb', $output);
        $this->assertStringContainsString(' backup.test2 ', $output);
        $this->assertStringContainsString(' test1 ', $output);
    }

    public function testWithoutTables(): void
    {
        $container = SqLiteHelper::createContainer();
        SqLiteHelper::clearDatabase($container);

        $command = $this->getCommand($container);

        $exitCode = $command->execute([]);
        $output = $command->getDisplay(true);

        $this->assertSame(ExitCode::UNSPECIFIED_ERROR, $exitCode);
        $this->assertStringContainsString('Your database does not contain any tables yet.', $output);
    }

    private function getCommand(ContainerInterface $container): CommandTester
    {
        return new CommandTester(
            new ListTablesCommand(
                new ListTablesService(
                    $container->get(ConnectionInterface::class),
                    $container->get(MigrationService::class),
                    $container->get(Migrator::class),
                ),
            ),
        );
    }
}
