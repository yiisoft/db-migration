<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Driver\Oracle;

use Yiisoft\Db\Migration\Tests\Common\Command\AbstractCreateCommandTest;
use Yiisoft\Db\Migration\Tests\Support\Factory\OracleFactory;
use Yiisoft\Db\Migration\Tests\Support\Helper\ContainerConfig;

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
