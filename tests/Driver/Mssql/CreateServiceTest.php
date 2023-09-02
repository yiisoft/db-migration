<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Driver\Mssql;

use Yiisoft\Db\Migration\Tests\Common\Service\Generate\AbstractCreateServiceTest;
use Yiisoft\Db\Migration\Tests\Support\Factory\MssqlFactory;

/**
 * @group mssql
 */
final class CreateServiceTest extends AbstractCreateServiceTest
{
    public function setUp(): void
    {
        parent::setUp();
        $this->container = MssqlFactory::createContainer();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        MssqlFactory::clearDatabase($this->container);
    }
}
