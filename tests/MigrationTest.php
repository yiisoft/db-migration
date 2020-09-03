<?php
namespace Yiisoft\Yii\Db\Migration\Tests;

/**
 * @group paths
 */
final class MigrationTest extends TestCase
{
    public function testExecute() {
        $db = $this->db;
        $db->createCommand('CREATE TABLE test_table (id int)')->execute();

        $migration = new Migration($db);

        $migration->execute('DROP TABLE test_table');
        $this->assertEmpty($db->getSchema()->getTableSchema('test'));
    }
}
