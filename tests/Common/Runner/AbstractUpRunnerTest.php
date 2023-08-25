<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Common\Runner;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Yiisoft\Yii\Db\Migration\Migrator;
use Yiisoft\Yii\Db\Migration\Runner\UpdateRunner;
use Yiisoft\Yii\Db\Migration\Tests\Support\Stub\StubMigration;

abstract class AbstractUpRunnerTest extends TestCase
{
    protected ContainerInterface $container;

    public function testWithoutIO(): void
    {
        $runner = new UpdateRunner($this->container->get(Migrator::class));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('You need to set output decorator via `setIO()`.');

        $runner->run(new StubMigration());
    }
}
