<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration;

use Yiisoft\Db\Migration\Service\MigrationService;

/**
 * Configuration and migration services of an additional named database.
 *
 * @internal
 */
final class DatabaseSetup
{
    public function __construct(
        public readonly DatabaseConfig $config,
        public readonly Migrator $migrator,
        public readonly MigrationService $migrationService,
    ) {}
}
