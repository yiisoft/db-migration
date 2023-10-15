<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Support\Migrations;

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;
use Yiisoft\Db\Migration\TransactionalMigrationInterface;

/**
 * Execute SQL
 */
final class M231015155500ExecuteSql implements RevertibleMigrationInterface, TransactionalMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $b->execute(
            <<<SQL
            CREATE TABLE person (
                id INT,
                first_name VARCHAR(100),
                last_name VARCHAR(100)
            )
            SQL,
        );
    }

    public function down(MigrationBuilder $b): void
    {
        $b->execute('DROP TABLE person');
    }
}
