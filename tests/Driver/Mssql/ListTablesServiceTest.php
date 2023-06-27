<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Driver\Mssql;

use Yiisoft\Yii\Db\Migration\Tests\Common\Service\Database\AbstractListTablesServiceTest;
use Yiisoft\Yii\Db\Migration\Tests\Support\Factory\MssqlFactory;

/**
 * @group mssql
 */
final class ListTablesServiceTest extends AbstractListTablesServiceTest
{
    public function setup(): void
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
