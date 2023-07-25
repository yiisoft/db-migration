<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Common\Command;

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

abstract class AbstractListTablesCommandTest extends TestCase
{
    protected ContainerInterface $container;

    public function testExecuted(): void
    {
        $db = $this->container->get(ConnectionInterface::class);

        if ($db->getDriverName() === 'oci') {
            $this->markTestSkipped('Should be fixed for Oracle.');
        }

        DbHelper::createTable($this->container, 'the_post', ['name' => 'string']);
        DbHelper::createTable($this->container, 'the_user', ['name' => 'string']);

        $command = $this->getCommand($this->container);

        $exitCode = $command->execute([]);
        $output = $command->getDisplay(true);

        $this->assertSame(ExitCode::OK, $exitCode);
        $this->assertStringContainsString('List of tables for database: yiitest', $output);
        $this->assertStringContainsString('the_post', $output);
        $this->assertStringContainsString('the_user', $output);
    }

    public function testWithoutTables(): void
    {
        $db = $this->container->get(ConnectionInterface::class);

        if ($db->getDriverName() === 'sqlsrv') {
            $this->markTestSkipped('Should be fixed for MsSQL.');
        }

        $command = $this->getCommand($this->container);

        $exitCode = $command->execute([]);
        $output = $command->getDisplay(true);

        $this->assertSame(ExitCode::UNSPECIFIED_ERROR, $exitCode);
        $this->assertStringContainsString('Your database does not contain any tables yet.', $output);
    }

    protected function getCommand(ContainerInterface $container): CommandTester
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
