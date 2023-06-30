<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Driver\Mysql;

use Psr\Container\ContainerInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Yii\Db\Migration\Tests\Common\AbstractColumnTypesTest;
use Yiisoft\Yii\Db\Migration\Tests\Support\Factory\MysqlFactory;

/**
 * @group mysql
 */
final class ColumnTypesTest extends AbstractColumnTypesTest
{
    private ContainerInterface $container;

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
    }
}
