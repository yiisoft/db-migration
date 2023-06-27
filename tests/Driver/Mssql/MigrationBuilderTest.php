<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Driver\Mssql;

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Yii\Db\Migration\Tests\Common\AbstractMigrationBuilderTest;
use Yiisoft\Yii\Db\Migration\Tests\Support\Factory\MssqlFactory;

/**
 * @group mssql
 */
final class MigrationBuilderTest extends AbstractMigrationBuilderTest
{
    public function setup(): void
    {
        $this->container = MssqlFactory::createContainer();

        $this->db = $this->container->get(ConnectionInterface::class);

        parent::setUp();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        MssqlFactory::clearDatabase($this->container);

        $this->db->close();
    }
}
