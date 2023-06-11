<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Support\Stub;

use Yiisoft\Yii\Db\Migration\MigrationBuilder;
use Yiisoft\Yii\Db\Migration\RevertibleMigrationInterface;

final class StubRevertibleMigration implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
    }

    public function down(MigrationBuilder $b): void
    {
    }
}
