<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Support\MigrationsExtra;

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;
use Yiisoft\Db\Migration\TransactionalMigrationInterface;

final class M231108183919Empty implements RevertibleMigrationInterface, TransactionalMigrationInterface
{
    public function up(MigrationBuilder $b): void {}

    public function down(MigrationBuilder $b): void {}
}
