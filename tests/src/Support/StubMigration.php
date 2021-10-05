<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Support;

use Yiisoft\Yii\Db\Migration\MigrationBuilder;
use Yiisoft\Yii\Db\Migration\MigrationInterface;

final class StubMigration implements MigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
    }
}
