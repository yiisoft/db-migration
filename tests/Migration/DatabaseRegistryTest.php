<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Migration;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Migration\DatabaseConfig;
use Yiisoft\Db\Migration\DatabaseRegistry;
use Yiisoft\Db\Migration\Informer\NullMigrationInformer;
use Yiisoft\Injector\Injector;

final class DatabaseRegistryTest extends TestCase
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
        new DatabaseRegistry(new Injector(), new NullMigrationInformer(), [
            $name => new DatabaseConfig($this->createMock(ConnectionInterface::class)),
        ]);
    }

    public function testRejectsDefaultName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The "default" database is configured by the existing migration settings and can\'t be redefined.',
        );
        new DatabaseRegistry(new Injector(), new NullMigrationInformer(), [
            'default' => new DatabaseConfig($this->createMock(ConnectionInterface::class)),
        ]);
    }

    public function testCannotCreateSetupForDefaultDatabase(): void
    {
        $registry = new DatabaseRegistry(new Injector(), new NullMigrationInformer());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown additional database: default.');
        $registry->createSetup('default');
    }

    public function testCannotCreateSetupForUnknownDatabase(): void
    {
        $registry = new DatabaseRegistry(new Injector(), new NullMigrationInformer());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown additional database: missing.');
        $registry->createSetup('missing');
    }

    public function testNamesInConfigurationOrder(): void
    {
        $config = new DatabaseConfig($this->createMock(ConnectionInterface::class));
        $registry = new DatabaseRegistry(new Injector(), new NullMigrationInformer(), [
            'maps' => $config,
            'analytics' => $config,
        ]);
        self::assertSame(['maps', 'analytics'], $registry->getNames());
    }

    public function testCreateSetup(): void
    {
        $config = new DatabaseConfig(
            $this->createMock(ConnectionInterface::class),
            historyTable: '{{%maps_migration}}',
            migrationNameLimit: 100,
        );
        $registry = new DatabaseRegistry(new Injector(), new NullMigrationInformer(), ['maps' => $config]);

        $setup = $registry->createSetup('maps');

        self::assertSame($config, $setup->config);
        self::assertSame(100, $setup->migrator->getMigrationNameLimit());
        self::assertNotSame($setup->migrator, $registry->createSetup('maps')->migrator);
    }
}
