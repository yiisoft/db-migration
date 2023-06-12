<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Driver\Sqlite;

use Psr\Container\ContainerInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Yii\Db\Migration\Tests\Common\AbstractColumnTypesTest;
use Yiisoft\Yii\Db\Migration\Tests\Support\Factory\SqLiteFactory;

/**
 * @group sqlite
 */
final class ColumnTypesTest extends AbstractColumnTypesTest
{
    private ContainerInterface $container;

    public function setup(): void
    {
        parent::setUp();
        $this->container = SqLiteFactory::createContainer();
        $this->db = $this->container->get(ConnectionInterface::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        SqLiteFactory::clearDatabase($this->container);
    }
}
