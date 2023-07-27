<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Driver\Pgsql;

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Db\Migration\Tests\Common\Command\AbstractListTablesCommandTest;
use Yiisoft\Yii\Db\Migration\Tests\Support\Factory\PostgreSqlFactory;
use Yiisoft\Yii\Db\Migration\Tests\Support\Helper\DbHelper;

/**
 * @group pgsql
 */
final class ListTablesCommandTest extends AbstractListTablesCommandTest
{
    public function setUp(): void
    {
        parent::setUp();
        $this->container = PostgreSqlFactory::createContainer();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        PostgreSqlFactory::clearDatabase($this->container);
    }

    public function testSeveralSchemas(): void
    {
        $db = $this->container->get(ConnectionInterface::class);

        PostgreSqlFactory::createSchema($this->container, 'backup');
        DbHelper::createTable($db, 'public.test1', ['name' => 'string']);
        DbHelper::createTable($db, 'backup.test2', ['name' => 'string']);

        $command = $this->getCommand($this->container);

        $exitCode = $command->execute([]);
        $output = $command->getDisplay(true);

        $this->assertSame(ExitCode::OK, $exitCode);
        $this->assertStringContainsString('List of tables for database: yiitest', $output);
        $this->assertStringContainsString(' backup.test2 ', $output);
        $this->assertStringContainsString(' test1 ', $output);
    }
}
