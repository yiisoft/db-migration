<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Driver\Pgsql;

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Yii\Db\Migration\Tests\Common\AbstractMigratorTest;
use Yiisoft\Yii\Db\Migration\Tests\Support\Factory\PostgreSqlFactory;

/**
 * @group pgsql
 */
final class MigratorTest extends AbstractMigratorTest
{
    public function setUp(): void
    {
        parent::setUp();
        $this->container = PostgreSqlFactory::createContainer();
        $this->db = $this->container->get(ConnectionInterface::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        PostgreSqlFactory::clearDatabase($this->container);
        $this->db->close();
    }
}
