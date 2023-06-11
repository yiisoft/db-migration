<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Common;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Yii\Db\Migration\Tests\Support\Provider\ColumnTypes;

abstract class AbstractColumnTypesTest extends TestCase
{
    protected ConnectionInterface $db;

    public function testGetColumnType(): void
    {
        $qb = $this->db->getQueryBuilder();
        $columnTypes = (new ColumnTypes($this->db))->getColumnTypes();

        foreach ($columnTypes as $item) {
            [$column, $builder, $expected] = $item;

            $driverName = $this->db->getDriverName();

            if (isset($item[3][$driverName])) {
                $expectedColumnSchemaBuilder = $item[3][$driverName];
            } elseif (isset($item[3]) && !is_array($item[3])) {
                $expectedColumnSchemaBuilder = $item[3];
            } else {
                $expectedColumnSchemaBuilder = $column;
            }

            $this->assertEquals($expectedColumnSchemaBuilder, $builder->asString());
            $this->assertEquals($expected, $qb->getColumnType($column));
            $this->assertEquals($expected, $qb->getColumnType($builder));
        }

        $this->db->close();
    }
}
