<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Command\Context;

use Yiisoft\Db\Migration\Migrator;
use Yiisoft\Db\Migration\Service\MigrationService;

/**
 * Services of a command that works with migrations of a single database.
 *
 * @internal
 */
final class MigrationContext
{
    public function __construct(
        public readonly Migrator $migrator,
        public readonly MigrationService $migrationService,
    ) {}
}
