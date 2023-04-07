<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Common;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Driver\Pdo\PdoConnectionInterface;
use Yiisoft\Yii\Db\Migration\Tests\Provider\ColumnTypes;
use Yiisoft\Yii\Db\Migration\Tests\Support\Helper\PostgreSqlHelper;
use Yiisoft\Yii\Db\Migration\Tests\Support\Helper\SqLiteHelper;

class ColumnTypesTest extends TestCase
{
    private ContainerInterface $container;
    private ConnectionInterface $dbPgsql;
    private ConnectionInterface $dbSqlite;

    /**
     * @dataProvider dbProvider
     */
    public function testGetColumnType(PdoConnectionInterface $db): void
    {
        $qb = $db->getQueryBuilder();
        $columnTypes = (new ColumnTypes($db))->getColumnTypes();

        foreach ($columnTypes as $item) {
            [$column, $builder, $expected] = $item;

            $driverName = $db->getDriver()->getDriverName();
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

        $db->close();
    }

    public function dbProvider()
    {
        $this->preparePostgreSql();
        $this->prepareSqLite();
        return [
            [
                $this->dbPgsql,
            ],
            [
                $this->dbSqlite,
            ],
        ];
    }

    private function preparePostgreSql(): void
    {
        $this->container = PostgreSqlHelper::createContainer();
        PostgreSqlHelper::clearDatabase($this->container);
        $this->dbPgsql = $this->container->get(ConnectionInterface::class);
    }

    private function prepareSqLite(): void
    {
        $this->container = SqLiteHelper::createContainer();
        SqLiteHelper::clearDatabase($this->container);
        $this->dbSqlite = $this->container->get(ConnectionInterface::class);
    }
}
