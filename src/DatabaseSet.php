<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration;

use Yiisoft\Db\Connection\ConnectionInterface;

/**
 * Configuration of an additional database and its migrations.
 */
final class DatabaseSet
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
        public readonly string $historyTable = '{{%migration}}',
        public readonly ?int $migrationNameLimit = 180,
        public readonly bool $useTablePrefix = true,
        public readonly ?int $maxSqlOutputLength = null,
    ) {}
}
