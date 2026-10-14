<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Migration;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Migration\DatabaseSet;
use Yiisoft\Db\Migration\DatabaseSetRegistry;
use Yiisoft\Db\Migration\Informer\NullMigrationInformer;
use Yiisoft\Injector\Injector;

final class DatabaseSetRegistryTest extends TestCase
{
    public static function invalidNames(): array
    {
        return [[''], [' '], [0]];
    }

    #[DataProvider('invalidNames')]
    public function testRejectsInvalidNames(string|int $name): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Database names must be non-empty strings.');
        new DatabaseSetRegistry(new Injector(), new NullMigrationInformer(), [
            $name => new DatabaseSet($this->createMock(ConnectionInterface::class)),
        ]);
    }

    public function testRejectsDefaultName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The "default" database is configured by the existing migration settings and can\'t be redefined.',
        );
        new DatabaseSetRegistry(new Injector(), new NullMigrationInformer(), [
            'default' => new DatabaseSet($this->createMock(ConnectionInterface::class)),
        ]);
    }

    public function testDefaultUsesExistingServices(): void
    {
        $registry = new DatabaseSetRegistry(new Injector(), new NullMigrationInformer());
        self::assertNull($registry->createContext('default'));
    }

    public function testCannotCreateContextForUnknownDatabase(): void
    {
        $registry = new DatabaseSetRegistry(new Injector(), new NullMigrationInformer());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown database set: missing.');
        $registry->createContext('missing');
    }

    public function testDefaultIsFirstFollowedByConfigurationOrder(): void
    {
        $set = new DatabaseSet($this->createMock(ConnectionInterface::class));
        $registry = new DatabaseSetRegistry(new Injector(), new NullMigrationInformer(), [
            'maps' => $set,
            'analytics' => $set,
        ]);
        self::assertSame(['default', 'maps', 'analytics'], $registry->getNames());
    }
}
