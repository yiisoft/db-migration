<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Driver\Oracle;

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Yii\Db\Migration\Tests\Common\AbstractMigratorTest;
use Yiisoft\Yii\Db\Migration\Tests\Support\Factory\OracleFactory;

/**
 * @group oracle
 */
final class MigratorTest extends AbstractMigratorTest
{
    public function setUp(): void
    {
        parent::setUp();
        $this->container = OracleFactory::createContainer();
        $this->db = $this->container->get(ConnectionInterface::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        OracleFactory::clearDatabase($this->container);
        $this->db->close();
    }
}
