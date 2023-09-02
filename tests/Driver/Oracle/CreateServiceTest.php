<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Driver\Oracle;

use Yiisoft\Db\Migration\Tests\Common\Service\Generate\AbstractCreateServiceTest;
use Yiisoft\Db\Migration\Tests\Support\Factory\OracleFactory;

/**
 * @group oracle
 */
final class CreateServiceTest extends AbstractCreateServiceTest
{
    public function setUp(): void
    {
        parent::setUp();
        $this->container = OracleFactory::createContainer();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        OracleFactory::clearDatabase($this->container);
    }
}
