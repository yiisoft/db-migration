<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Driver\Sqlite;

use Yiisoft\Db\Migration\Tests\Common\Command\AbstractRedoCommandTest;
use Yiisoft\Db\Migration\Tests\Support\Factory\SqLiteFactory;

/**
 * @group sqlite
 */
final class RedoCommandTest extends AbstractRedoCommandTest
{
    public function setUp(): void
    {
        parent::setUp();
        $this->container = SqLiteFactory::createContainer();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        SqLiteFactory::clearDatabase($this->container);
    }
}
