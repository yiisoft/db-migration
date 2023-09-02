<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Driver\Pgsql;

use Yiisoft\Db\Migration\Tests\Common\Command\AbstractCreateCommandTest;
use Yiisoft\Db\Migration\Tests\Support\Factory\PostgreSqlFactory;
use Yiisoft\Db\Migration\Tests\Support\Helper\ContainerConfig;

/**
 * @group pgsql
 */
final class CreateCommandTest extends AbstractCreateCommandTest
{
    public function setUp(): void
    {
        parent::setUp();
        $this->container = PostgreSqlFactory::createContainer();
        $this->driverName = 'pgsql';
    }

    public function tearDown(): void
    {
        parent::tearDown();
        PostgreSqlFactory::clearDatabase($this->container);
    }

    public function testWithoutTablePrefix(): void
    {
        $containerConfig = new ContainerConfig();
        $containerConfig->useTablePrefix = false;

        $this->container = PostgreSqlFactory::createContainer($containerConfig);

        parent::testWithoutTablePrefix();
    }
}
