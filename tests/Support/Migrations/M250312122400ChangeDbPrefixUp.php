<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Support\Migrations;

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\TransactionalMigrationInterface;

/**
 * Change DB prefix in {@see M250312122400ChangeDbPrefixUp::up()} method
 */
final class M250312122400ChangeDbPrefixUp implements TransactionalMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $b->getDb()->setTablePrefix('prefix_');

        $b->insert('{{%user}}', [
            'name' => 'John',
        ]);
    }
}
