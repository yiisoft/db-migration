<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Driver\Mssql;

use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Migration\Tests\Common\Command\AbstractDatabaseCommandsTest;
use Yiisoft\Db\Mssql\Connection;
use Yiisoft\Db\Mssql\Driver;
use Yiisoft\Test\Support\SimpleCache\MemorySimpleCache;

/**
 * @group mssql
 */
final class DatabaseCommandsTest extends AbstractDatabaseCommandsTest
{
    private const DATABASE = 'yiitest';

    /** @var ConnectionInterface[] */
    private array $createdConnections = [];

    protected function tearDown(): void
    {
        parent::tearDown();
        foreach ($this->createdConnections as $connection) {
            $this->clearDatabase($connection);
            $connection->close();
        }
        $this->createdConnections = [];
    }

    protected function createConnection(string $name): ConnectionInterface
    {
        $database = $name === 'default' ? self::DATABASE : self::DATABASE . '_' . $name;
        if ($database !== self::DATABASE) {
            $this->ensureDatabase($database);
        }

        $connection = $this->connect($database);
        $this->clearDatabase($connection);
        $this->createdConnections[] = $connection;

        return $connection;
    }

    protected function createUnavailableConnection(): ConnectionInterface
    {
        return $this->connect(self::DATABASE . '_missing');
    }

    private function connect(string $database): ConnectionInterface
    {
        return new Connection(
            new Driver(
                'sqlsrv:Server=127.0.0.1,1433;Database=' . $database . ';Encrypt=no',
                'SA',
                'YourStrong!Passw0rd',
            ),
            new SchemaCache(new MemorySimpleCache()),
        );
    }

    private function ensureDatabase(string $database): void
    {
        $connection = $this->connect(self::DATABASE);
        $id = $connection
            ->createCommand('SELECT DB_ID(:name)', [':name' => $database])
            ->queryScalar();
        if ($id === null) {
            $connection
                ->createCommand('CREATE DATABASE ' . $connection->getQuoter()->quoteTableName($database))
                ->execute();
        }
        $connection->close();
    }

    private function clearDatabase(ConnectionInterface $connection): void
    {
        $quoter = $connection->getQuoter();

        $foreignKeys = $connection
            ->createCommand(
                'SELECT OBJECT_SCHEMA_NAME(parent_object_id) AS table_schema,'
                . ' OBJECT_NAME(parent_object_id) AS table_name, name FROM sys.foreign_keys',
            )
            ->queryAll();
        foreach ($foreignKeys as $foreignKey) {
            $connection
                ->createCommand(
                    'ALTER TABLE ' . $quoter->quoteTableName($foreignKey['table_schema'] . '.' . $foreignKey['table_name'])
                    . ' DROP CONSTRAINT ' . $quoter->quoteColumnName($foreignKey['name']),
                )
                ->execute();
        }

        $tables = $connection
            ->createCommand('SELECT SCHEMA_NAME(schema_id) AS table_schema, name FROM sys.tables')
            ->queryAll();
        foreach ($tables as $table) {
            $connection
                ->createCommand('DROP TABLE ' . $quoter->quoteTableName($table['table_schema'] . '.' . $table['name']))
                ->execute();
        }
    }
}
