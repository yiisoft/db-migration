<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Common\Service\Database;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Yii\Db\Migration\Migrator;
use Yiisoft\Yii\Db\Migration\Service\Database\ListTablesService;
use Yiisoft\Yii\Db\Migration\Service\MigrationService;

abstract class AbstractListTablesServiceTest extends TestCase
{
    protected ContainerInterface $container;

    public function testWithoutIO(): void
    {
        $service = new ListTablesService(
            $this->container->get(ConnectionInterface::class),
            $this->container->get(MigrationService::class),
            $this->container->get(Migrator::class),
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('You need to set output decorator via `setIO()`.');

        $service->run();
    }
}
