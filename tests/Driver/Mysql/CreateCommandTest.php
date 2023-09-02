<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Driver\Mysql;

use Yiisoft\Db\Migration\Tests\Common\Command\AbstractCreateCommandTest;
use Yiisoft\Db\Migration\Tests\Support\Factory\MysqlFactory;
use Yiisoft\Db\Migration\Tests\Support\Helper\ContainerConfig;

/**
 * @group mysql
 */
final class CreateCommandTest extends AbstractCreateCommandTest
{
    public function setUp(): void
    {
        parent::setUp();
        $this->container = MysqlFactory::createContainer();
        $this->driverName = 'mysql';
    }

    public function tearDown(): void
    {
        parent::tearDown();
        MysqlFactory::clearDatabase($this->container);
    }

    public function testWithoutTablePrefix(): void
    {
        $containerConfig = new ContainerConfig();
        $containerConfig->useTablePrefix = false;

        $this->container = MysqlFactory::createContainer($containerConfig);

        parent::testWithoutTablePrefix();
    }
}
