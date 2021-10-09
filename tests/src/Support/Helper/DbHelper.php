<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Support\Helper;

use Psr\Container\ContainerInterface;
use Yiisoft\Db\Connection\ConnectionInterface;

final class DbHelper
{
    public static function createTable(ContainerInterface $container, string $name, array $fields): void
    {
        $container->get(ConnectionInterface::class)
            ->createCommand()
            ->createTable($name, $fields)
            ->execute();
    }

    public static function insert(ContainerInterface $container, string $table, array $columns): void
    {
        $container->get(ConnectionInterface::class)
            ->createCommand()
            ->insert($table, $columns)
            ->execute();
    }
}
