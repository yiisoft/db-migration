<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Migration;

use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Migration\Command\CommandFactory;
use Yiisoft\Db\Migration\DatabaseSet;
use Yiisoft\Db\Migration\DatabaseSetRegistry;
use Yiisoft\Db\Migration\Informer\NullMigrationInformer;
use Yiisoft\Injector\Injector;

final class CommandFactoryTest extends TestCase
{
    public function testRequiresNamedDefault(): void
    {
        $factory = new CommandFactory(new DatabaseSetRegistry(new Injector(), new NullMigrationInformer()));
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Configure databases["default"] when using CommandFactory.');
        $factory->create('up');
    }

    public function testUnknownCommand(): void
    {
        $factory = new CommandFactory(new DatabaseSetRegistry(new Injector(), new NullMigrationInformer(), [
            'default' => new DatabaseSet($this->createMock(ConnectionInterface::class)),
        ]));
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown migration command: unknown.');
        $factory->create('unknown');
    }
}
