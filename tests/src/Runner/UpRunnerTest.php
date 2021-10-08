<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Runner;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Yiisoft\Yii\Db\Migration\Migrator;
use Yiisoft\Yii\Db\Migration\Runner\UpdateRunner;
use Yiisoft\Yii\Db\Migration\Tests\Support\SqLiteHelper;
use Yiisoft\Yii\Db\Migration\Tests\Support\StubMigration;

final class UpRunnerTest extends TestCase
{
    public function testWithoutIO(): void
    {
        $container = SqLiteHelper::createContainer();

        $runner = new UpdateRunner(
            $container->get(Migrator::class),
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Need set output decorator via `setIO()`.');
        $runner->run(new StubMigration());
    }
}
