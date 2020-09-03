<?php
namespace Yiisoft\Yii\Db\Migration\Tests;

use Yiisoft\Yii\Db\Migration\Migration;
use Yiisoft\Db\Connection\Connection;

/**
 * @group paths
 */
final class MigrationTest extends TestCase
{
    public function testExecute() {
        $db = $this->db;
        $db->createCommand('CREATE TABLE test_table (id int)')->execute();
        $migration = $this->getMigration($db);

        $migration->execute('DROP TABLE test_table');
        $this->assertEmpty($db->getSchema()->getTableSchema('test'));
    }

    /**
     * Returns Migration mock object
     * @param Connection $db
     * @return Migration
     */
    private function getMigration(Connection $db): Migration
    {
        $migration = $this->getMockBuilder(Migration::class)
            ->setConstructorArgs([$db])
            ->onlyMethods(['up', 'down'])
            ->getMock();
        return $migration;
    }
}
