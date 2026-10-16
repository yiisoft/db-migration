<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration;

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Migration\Service\Generate\CreateService;

/**
 * Configuration of an additional named database and its migrations.
 */
final class DatabaseConfig
{
    /**
     * @param string[] $sourceNamespaces
     * @param string[] $sourcePaths
     */
    public function __construct(
        public readonly ConnectionInterface $db,
        public readonly string $newMigrationNamespace = '',
        public readonly string $newMigrationPath = '',
        public readonly array $sourceNamespaces = [],
        public readonly array $sourcePaths = [],
        public readonly string $historyTable = Migrator::DEFAULT_HISTORY_TABLE,
        public readonly ?int $migrationNameLimit = Migrator::DEFAULT_MIGRATION_NAME_LIMIT,
        public readonly bool $useTablePrefix = CreateService::DEFAULT_USE_TABLE_PREFIX,
        public readonly ?int $maxSqlOutputLength = null,
    ) {}
}
