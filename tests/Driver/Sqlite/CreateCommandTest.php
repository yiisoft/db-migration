<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Driver\Sqlite;

use Yiisoft\Db\Migration\Tests\Common\Command\AbstractCreateCommandTest;
use Yiisoft\Db\Migration\Tests\Support\Factory\SqLiteFactory;
use Yiisoft\Db\Migration\Tests\Support\Helper\ContainerConfig;

/**
 * @group sqlite
 */
final class CreateCommandTest extends AbstractCreateCommandTest
{
    public function setUp(): void
    {
        parent::setUp();
        $this->container = SqLiteFactory::createContainer();
        $this->driverName = 'sqlite';
    }

    public function tearDown(): void
    {
        parent::tearDown();
        SqLiteFactory::clearDatabase($this->container);
    }

    public function testWithoutTablePrefix(): void
    {
        $containerConfig = new ContainerConfig();
        $containerConfig->useTablePrefix = false;

        $this->container = SqLiteFactory::createContainer($containerConfig);

        parent::testWithoutTablePrefix();
    }
}
