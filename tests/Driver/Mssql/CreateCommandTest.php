<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Driver\Mssql;

use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Db\Migration\Tests\Common\Command\AbstractCreateCommandTest;
use Yiisoft\Yii\Db\Migration\Tests\Support\Factory\MssqlFactory;
use Yiisoft\Yii\Db\Migration\Tests\Support\Helper\ContainerConfig;
use Yiisoft\Yii\Db\Migration\Tests\Support\Helper\MigrationHelper;

/**
 * @group mssql
 */
final class CreateCommandTest extends AbstractCreateCommandTest
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

    public function testWithoutTablePrefix(): void
    {
        $containerConfig = new ContainerConfig();
        $containerConfig->useTablePrefix = false;

        $this->container = MssqlFactory::createContainer($containerConfig);

        parent::testWithoutTablePrefix();
    }
}
