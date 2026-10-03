<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Support\M260101000002Group;

use LogicException;
use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

final class M260101000001First implements RevertibleMigrationInterface
{
    public function __construct()
    {
        throw new LogicException('Marking must not instantiate migrations.');
    }

    public function up(MigrationBuilder $b): void
    {
        throw new LogicException('Marking must not execute up().');
    }

    public function down(MigrationBuilder $b): void
    {
        throw new LogicException('Marking must not execute down().');
    }
}
