<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Driver\Oracle;

use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Migration\Tests\Common\Command\AbstractDatabaseCommandsTest;
use Yiisoft\Db\Oracle\Connection;
use Yiisoft\Db\Oracle\Driver;
use Yiisoft\Test\Support\SimpleCache\MemorySimpleCache;

use function strtoupper;

/**
 * Each database is a separate schema owned by its own common user, so the system schema is never cleared.
 *
 * @group oracle
 */
final class DatabaseCommandsTest extends AbstractDatabaseCommandsTest
{
    private const USER_PREFIX = 'C##YIITEST_';
    private const PASSWORD = 'root';

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
        $user = self::USER_PREFIX . strtoupper($name);
        $this->ensureUser($user);

        $connection = $this->connect($user, self::PASSWORD);
        $this->clearDatabase($connection);
        $this->createdConnections[] = $connection;

        return $connection;
    }

    protected function createUnavailableConnection(): ConnectionInterface
    {
        return $this->connect(self::USER_PREFIX . 'MISSING', self::PASSWORD);
    }

    private function connect(string $user, string $password): ConnectionInterface
    {
        return new Connection(
            new Driver('oci:dbname=localhost:1521/XE;charset=AL32UTF8', $user, $password),
            new SchemaCache(new MemorySimpleCache()),
        );
    }

    private function ensureUser(string $user): void
    {
        $connection = $this->connect('system', 'root');
        $exists = $connection
            ->createCommand('SELECT 1 FROM all_users WHERE username = :name', [':name' => $user])
            ->queryScalar();
        if ($exists === false) {
            $quotedUser = $connection->getQuoter()->quoteTableName($user);
            $connection
                ->createCommand('CREATE USER ' . $quotedUser . ' IDENTIFIED BY ' . self::PASSWORD)
                ->execute();
            $connection
                ->createCommand(
                    'GRANT CREATE SESSION, CREATE TABLE, CREATE SEQUENCE, CREATE TRIGGER, UNLIMITED TABLESPACE TO '
                    . $quotedUser,
                )
                ->execute();
        }
        $connection->close();
    }

    private function clearDatabase(ConnectionInterface $connection): void
    {
        $tables = $connection
            ->createCommand("SELECT table_name FROM user_tables WHERE dropped = 'NO'")
            ->queryColumn();
        foreach ($tables as $table) {
            $connection
                ->createCommand(
                    'DROP TABLE ' . $connection->getQuoter()->quoteTableName($table) . ' CASCADE CONSTRAINTS PURGE',
                )
                ->execute();
        }
        $connection->createCommand('PURGE RECYCLEBIN')->execute();
    }
}
