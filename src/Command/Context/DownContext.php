<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Command\Context;

use Yiisoft\Db\Migration\Migrator;
use Yiisoft\Db\Migration\Runner\DownRunner;
use Yiisoft\Db\Migration\Service\MigrationService;

/**
 * Services of the `migrate:down` command for a single database.
 *
 * @internal
 */
final class DownContext
{
    public function __construct(
        public readonly Migrator $migrator,
        public readonly MigrationService $migrationService,
        public readonly DownRunner $downRunner,
    ) {}
}
