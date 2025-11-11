<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Support\Stub;

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\MigrationInterface;

final class StubMigration implements MigrationInterface
{
    public function up(MigrationBuilder $b): void {}
}
