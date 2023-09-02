<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Driver\Pgsql;

use Yiisoft\Db\Migration\Tests\Common\Runner\AbstractDownRunnerTest;
use Yiisoft\Db\Migration\Tests\Support\Factory\PostgreSqlFactory;

/**
 * @group pgsql
 */
final class DownRunnerTest extends AbstractDownRunnerTest
{
    public function setUp(): void
    {
        parent::setUp();
        $this->container = PostgreSqlFactory::createContainer();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        PostgreSqlFactory::clearDatabase($this->container);
    }
}
