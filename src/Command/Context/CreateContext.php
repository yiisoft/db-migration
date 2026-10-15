<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Command\Context;

use Yiisoft\Db\Migration\Migrator;
use Yiisoft\Db\Migration\Service\Generate\CreateService;
use Yiisoft\Db\Migration\Service\MigrationService;

/**
 * Services of the `migrate:create` command for a single database.
 *
 * @internal
 */
final class CreateContext
{
    public function __construct(
        public readonly Migrator $migrator,
        public readonly MigrationService $migrationService,
        public readonly CreateService $createService,
    ) {}
}
