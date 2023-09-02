<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Driver\Sqlite;

use Yiisoft\Db\Migration\Tests\Common\Command\AbstractNewCommandTest;
use Yiisoft\Db\Migration\Tests\Support\Factory\SqLiteFactory;

/**
 * @group sqlite
 */
final class NewCommandTest extends AbstractNewCommandTest
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
}
