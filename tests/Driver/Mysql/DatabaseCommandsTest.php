<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Driver\Mysql;

use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Migration\Tests\Common\Command\AbstractDatabaseCommandsTest;
use Yiisoft\Db\Mysql\Connection;
use Yiisoft\Db\Mysql\Driver;
use Yiisoft\Test\Support\SimpleCache\MemorySimpleCache;

/**
 * @group mysql
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
            new Driver('mysql:host=127.0.0.1;dbname=' . $database . ';port=3306;charset=utf8', 'root', ''),
            new SchemaCache(new MemorySimpleCache()),
        );
    }

    private function ensureDatabase(string $database): void
    {
        $connection = $this->connect(self::DATABASE);
        $connection
            ->createCommand('CREATE DATABASE IF NOT EXISTS ' . $connection->getQuoter()->quoteTableName($database))
            ->execute();
        $connection->close();
    }

    private function clearDatabase(ConnectionInterface $connection): void
    {
        $tables = $connection
            ->createCommand(
                "SELECT table_name FROM information_schema.tables"
                . " WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE'",
            )
            ->queryColumn();
        if ($tables === []) {
            return;
        }

        $connection->createCommand('SET FOREIGN_KEY_CHECKS = 0')->execute();
        foreach ($tables as $table) {
            $connection
                ->createCommand('DROP TABLE ' . $connection->getQuoter()->quoteTableName($table))
                ->execute();
        }
        $connection->createCommand('SET FOREIGN_KEY_CHECKS = 1')->execute();
    }
}
