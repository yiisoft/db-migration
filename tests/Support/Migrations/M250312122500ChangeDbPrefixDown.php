<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Support\Migrations;

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;
use Yiisoft\Db\Migration\TransactionalMigrationInterface;

/**
 * Change DB prefix in {@see M250312122500ChangeDbPrefixDown::down()} method
 */
final class M250312122500ChangeDbPrefixDown implements RevertibleMigrationInterface, TransactionalMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $b->insert('prefix_user', [
            'name' => 'John',
        ]);
    }

    public function down(MigrationBuilder $b): void
    {
        $b->getDb()->setTablePrefix('prefix_');

        $b->delete('{{%user}}');
    }
}
