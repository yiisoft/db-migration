<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration;

use Yiisoft\Db\Migration\Runner\DownRunner;
use Yiisoft\Db\Migration\Runner\UpdateRunner;
use Yiisoft\Db\Migration\Service\Generate\CreateService;
use Yiisoft\Db\Migration\Service\MigrationService;

/**
 * Services for one execution against a named database set.
 *
 * @internal
 */
final class DatabaseContext
{
    public function __construct(
        public readonly Migrator $migrator,
        public readonly MigrationService $migrationService,
        public readonly CreateService $createService,
        public readonly DownRunner $downRunner,
        public readonly UpdateRunner $updateRunner,
    ) {}
}
