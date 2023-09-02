<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Driver\Mssql;

use Yiisoft\Db\Migration\Tests\Common\AbstractMigratorTest;
use Yiisoft\Db\Migration\Tests\Support\Factory\MssqlFactory;

/**
 * @group mssql
 */
final class MigratorTest extends AbstractMigratorTest
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
