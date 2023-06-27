<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Driver\Mssql;

use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Db\Migration\Tests\Common\Command\AbstractListTablesCommandTest;
use Yiisoft\Yii\Db\Migration\Tests\Support\Factory\MssqlSqlFactory;
use Yiisoft\Yii\Db\Migration\Tests\Support\Helper\DbHelper;

/**
 * @group mssql
 */
final class ListTablesCommandTest extends AbstractListTablesCommandTest
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
