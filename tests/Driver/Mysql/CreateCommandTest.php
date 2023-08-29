<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Driver\Mysql;

use Yiisoft\Yii\Db\Migration\Tests\Common\Command\AbstractCreateCommandTest;
use Yiisoft\Yii\Db\Migration\Tests\Support\Factory\MysqlFactory;
use Yiisoft\Yii\Db\Migration\Tests\Support\Helper\ContainerConfig;

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
