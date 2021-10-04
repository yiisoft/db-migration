<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Service\Database;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Yii\Db\Migration\Migrator;
use Yiisoft\Yii\Db\Migration\Service\Database\ListTablesService;
use Yiisoft\Yii\Db\Migration\Service\MigrationService;
use Yiisoft\Yii\Db\Migration\Tests\Support\SqlLiteHelper;

final class ListTablesServiceTest extends TestCase
{
    public function testWithoutIO(): void
    {
        $container = SqlLiteHelper::createContainer();

        $service = new ListTablesService(
            $container->get(ConnectionInterface::class),
            $container->get(MigrationService::class),
            $container->get(Migrator::class),
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Need set output decorator via `withIO()`.');
        $service->run();
    }
}
