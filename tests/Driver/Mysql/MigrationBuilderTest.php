<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Driver\Mysql;

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Yii\Db\Migration\Tests\Common\AbstractMigrationBuilderTest;
use Yiisoft\Yii\Db\Migration\Tests\Support\Factory\MysqlFactory;
use Yiisoft\Yii\Db\Migration\Tests\Support\Helper\DbHelper;

/**
 * @group mysql
 */
final class MigrationBuilderTest extends AbstractMigrationBuilderTest
{
    public function setUp(): void
    {
        $this->container = MysqlFactory::createContainer();

        $this->db = $this->container->get(ConnectionInterface::class);

        parent::setUp();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $tables = [
            'target_table',
            'test_table',
            'test',
        ];

        foreach ($tables as $table) {
            DbHelper::dropTable($this->db, $table);
        }

        $this->db->close();
    }
}
