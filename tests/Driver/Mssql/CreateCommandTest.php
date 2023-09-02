<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Driver\Mssql;

use Yiisoft\Db\Migration\Tests\Common\Command\AbstractCreateCommandTest;
use Yiisoft\Db\Migration\Tests\Support\Factory\MssqlFactory;
use Yiisoft\Db\Migration\Tests\Support\Helper\ContainerConfig;

/**
 * @group mssql
 */
final class CreateCommandTest extends AbstractCreateCommandTest
{
    public function setUp(): void
    {
        parent::setUp();
        $this->container = MssqlFactory::createContainer();
        $this->driverName = 'sqlsrv';
    }

    public function tearDown(): void
    {
        parent::tearDown();
        MssqlFactory::clearDatabase($this->container);
    }

    public function testWithoutTablePrefix(): void
    {
        $containerConfig = new ContainerConfig();
        $containerConfig->useTablePrefix = false;

        $this->container = MssqlFactory::createContainer($containerConfig);

        parent::testWithoutTablePrefix();
    }
}
