<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Driver\Mssql;

use Yiisoft\Yii\Db\Migration\Tests\Common\Command\AbstractNewCommandTest;
use Yiisoft\Yii\Db\Migration\Tests\Support\Factory\MssqlFactory;

/**
 * @group mssql
 */
final class NewCommandTest extends AbstractNewCommandTest
{
    public function setUp(): void
    {
        parent::setUp();
        $this->container = MssqlFactory::createContainer();
        $this->driverName = 'sqlsrv';
    }

    public function tearDown(): void
    {
        parent::tearDown();
        MssqlFactory::clearDatabase($this->container);
    }
}
