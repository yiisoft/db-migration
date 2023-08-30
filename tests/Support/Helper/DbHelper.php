<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Support\Helper;

use Yiisoft\Db\Connection\ConnectionInterface;

final class DbHelper
{
    public static function createTable(ConnectionInterface $db, string $name, array $fields): void
    {
        $command = $db->createCommand();

        if (self::checkSchema($db, $name)) {
            $command->dropTable($name);
        }

        $command->createTable($name, $fields)->execute();
    }

    public static function dropTable(ConnectionInterface $db, string $name): void
    {
        $command = $db->createCommand();

        if (self::checkSchema($db, $name)) {
            $command->dropTable($name)->execute();
        }
    }

    public static function checkSchema(ConnectionInterface $db, string $name): bool
    {
        $schema = $db->getSchema();
        $tableSchema = $schema->getTableSchema($name);

        return $tableSchema !== null;
    }

    public static function insert(ConnectionInterface $db, string $table, array $columns): void
    {
        $command = $db->createCommand();
        $command->insert($table, $columns)->execute();
    }
}
