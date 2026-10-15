<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Driver\Pgsql;

use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Migration\Tests\Common\Command\AbstractDatabaseCommandsTest;
use Yiisoft\Db\Pgsql\Connection;
use Yiisoft\Db\Pgsql\Driver;
use Yiisoft\Test\Support\SimpleCache\MemorySimpleCache;

/**
 * @group pgsql
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
            new Driver('pgsql:host=127.0.0.1;port=5432;dbname=' . $database, 'root', 'root'),
            new SchemaCache(new MemorySimpleCache()),
        );
    }

    private function ensureDatabase(string $database): void
    {
        $connection = $this->connect(self::DATABASE);
        $exists = $connection
            ->createCommand('SELECT 1 FROM pg_database WHERE datname = :name', [':name' => $database])
            ->queryScalar();
        if ($exists === false) {
            $connection
                ->createCommand('CREATE DATABASE ' . $connection->getQuoter()->quoteTableName($database))
                ->execute();
        }
        $connection->close();
    }

    private function clearDatabase(ConnectionInterface $connection): void
    {
        $connection->createCommand('DROP SCHEMA IF EXISTS "public" CASCADE')->execute();
        $connection->createCommand('CREATE SCHEMA "public"')->execute();
    }
}
