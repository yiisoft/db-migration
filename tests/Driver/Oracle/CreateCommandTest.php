<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Driver\Oracle;

use Yiisoft\Yii\Db\Migration\Tests\Common\Command\AbstractCreateCommandTest;
use Yiisoft\Yii\Db\Migration\Tests\Support\Factory\OracleFactory;
use Yiisoft\Yii\Db\Migration\Tests\Support\Helper\ContainerConfig;

/**
 * @group oracle
 */
final class CreateCommandTest extends AbstractCreateCommandTest
{
    public function setUp(): void
    {
        parent::setUp();
        $this->container = OracleFactory::createContainer();
        $this->driverName = 'oci';
    }

    public function tearDown(): void
    {
        parent::tearDown();
        OracleFactory::clearDatabase($this->container);
    }

    public function testWithoutTablePrefix(): void
    {
        $containerConfig = new ContainerConfig();
        $containerConfig->useTablePrefix = false;

        $this->container = OracleFactory::createContainer($containerConfig);

        parent::testWithoutTablePrefix();
    }
}
