<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Driver\Mssql;

use Yiisoft\Yii\Db\Migration\Tests\Common\Service\Generate\AbstractCreateServiceTest;
use Yiisoft\Yii\Db\Migration\Tests\Support\Factory\MssqlSqlFactory;

/**
 * @group mssql
 */
final class CreateServiceTest extends AbstractCreateServiceTest
{
    public function setup(): void
    {
        parent::setUp();
        $this->container = MssqlSqlFactory::createContainer();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        MssqlSqlFactory::clearDatabase($this->container);
    }
}
