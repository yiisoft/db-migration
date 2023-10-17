<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Support\Migrations;

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;
use Yiisoft\Db\Migration\TransactionalMigrationInterface;

final class M231017150317EmptyDown implements RevertibleMigrationInterface, TransactionalMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $b->createTable('chapter', [
            'id' => $b->primaryKey(),
            'name' => $b->string(100),
        ]);
    }

    public function down(MigrationBuilder $b): void
    {
    }
}
