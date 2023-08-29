<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Driver\Sqlite;

use Yiisoft\Yii\Db\Migration\Tests\Common\Command\AbstractHistoryCommandTest;
use Yiisoft\Yii\Db\Migration\Tests\Support\Factory\SqLiteFactory;

/**
 * @group sqlite
 */
final class HistoryCommandTest extends AbstractHistoryCommandTest
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
