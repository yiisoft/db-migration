<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Driver\Mysql;

use Yiisoft\Yii\Db\Migration\Tests\Common\Command\AbstractDownCommandTest;
use Yiisoft\Yii\Db\Migration\Tests\Support\Factory\MysqlFactory;

/**
 * @group mysql
 */
final class DownCommandTest extends AbstractDownCommandTest
{
    public function setUp(): void
    {
        parent::setUp();
        $this->container = MysqlFactory::createContainer();
        $this->driverName = 'mysql';
    }

    public function tearDown(): void
    {
        parent::tearDown();
        MysqlFactory::clearDatabase($this->container);
    }
}
