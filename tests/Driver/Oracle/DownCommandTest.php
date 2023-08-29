<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Driver\Oracle;

use Yiisoft\Yii\Db\Migration\Tests\Common\Command\AbstractDownCommandTest;
use Yiisoft\Yii\Db\Migration\Tests\Support\Factory\OracleFactory;

/**
 * @group oracle
 */
final class DownCommandTest extends AbstractDownCommandTest
{
    public function setUp(): void
    {
        parent::setUp();
        $this->container = OracleFactory::createContainer();
        $this->driverName = 'oci';
    }

    public function tearDown(): void
    {
        parent::tearDown();
        OracleFactory::clearDatabase($this->container);
    }
}
