<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Driver\Sqlite;

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Yii\Db\Migration\Tests\Common\AbstractMigratorTest;
use Yiisoft\Yii\Db\Migration\Tests\Support\Factory\SqLiteFactory;

/**
 * @group sqlite
 */
final class MigratorTest extends AbstractMigratorTest
{
    public function setUp(): void
    {
        parent::setUp();
        $this->container = SqLiteFactory::createContainer();
        $this->db = $this->container->get(ConnectionInterface::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        SqLiteFactory::clearDatabase($this->container);
        $this->db->close();
    }

    public function testGetMigrationNameLimitWithoutColumnSize(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Yiisoft\Db\Sqlite\DDLQueryBuilder::alterColumn is not supported by SQLite.');

        parent::testGetMigrationNameLimitWithoutColumnSize();
    }
}
