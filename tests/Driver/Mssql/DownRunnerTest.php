<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Driver\Mssql;

use Yiisoft\Db\Migration\Tests\Common\Runner\AbstractDownRunnerTest;
use Yiisoft\Db\Migration\Tests\Support\Factory\MssqlFactory;

/**
 * @group mssql
 */
final class DownRunnerTest extends AbstractDownRunnerTest
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
