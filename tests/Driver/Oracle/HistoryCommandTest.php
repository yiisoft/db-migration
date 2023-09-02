<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Driver\Oracle;

use Yiisoft\Db\Migration\Tests\Common\Command\AbstractHistoryCommandTest;
use Yiisoft\Db\Migration\Tests\Support\Factory\OracleFactory;

/**
 * @group oracle
 */
final class HistoryCommandTest extends AbstractHistoryCommandTest
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
