<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Driver\Mysql;

use Yiisoft\Db\Migration\Tests\Common\Runner\AbstractUpRunnerTest;
use Yiisoft\Db\Migration\Tests\Support\Factory\MysqlFactory;

/**
 * @group mysql
 */
final class UpRunnerTest extends AbstractUpRunnerTest
{
    public function setUp(): void
    {
        parent::setUp();
        $this->container = MysqlFactory::createContainer();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        MysqlFactory::clearDatabase($this->container);
    }
}
