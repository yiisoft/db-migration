<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Driver\Mysql;

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Yii\Db\Migration\Tests\Common\AbstractMigratorTest;
use Yiisoft\Yii\Db\Migration\Tests\Support\Factory\MysqlFactory;

/**
 * @group mysql
 */
final class MigratorTest extends AbstractMigratorTest
{
    public function setUp(): void
    {
        parent::setUp();
        $this->container = MysqlFactory::createContainer();
        $this->db = $this->container->get(ConnectionInterface::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        MysqlFactory::clearDatabase($this->container);
        $this->db->close();
    }
}
