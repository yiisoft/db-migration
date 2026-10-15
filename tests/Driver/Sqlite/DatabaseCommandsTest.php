<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Driver\Sqlite;

use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Migration\Tests\Common\Command\AbstractDatabaseCommandsTest;
use Yiisoft\Db\Sqlite\Connection;
use Yiisoft\Db\Sqlite\Driver;
use Yiisoft\Test\Support\SimpleCache\MemorySimpleCache;

/**
 * @group sqlite
 */
final class DatabaseCommandsTest extends AbstractDatabaseCommandsTest
{
    protected function createConnection(string $name): ConnectionInterface
    {
        return new Connection(new Driver('sqlite::memory:'), new SchemaCache(new MemorySimpleCache()));
    }

    protected function createUnavailableConnection(): ConnectionInterface
    {
        return new Connection(
            new Driver('sqlite:' . $this->directory . '/missing/maps.sqlite'),
            new SchemaCache(new MemorySimpleCache()),
        );
    }
}
