<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Support\Stub;

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

final class StubRevertibleMigration implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
    }

    public function down(MigrationBuilder $b): void
    {
    }
}
