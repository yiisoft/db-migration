<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Driver\Oracle;

use Yiisoft\Db\Migration\Tests\Common\Service\AbstractMigrationServiceTest;
use Yiisoft\Db\Migration\Tests\Support\Factory\OracleFactory;

/**
 * @group oracle
 */
final class MigrationServiceTest extends AbstractMigrationServiceTest
{
    public function setUp(): void
    {
        parent::setUp();
        $this->container = OracleFactory::createContainer();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        OracleFactory::clearDatabase($this->container);
    }
}
