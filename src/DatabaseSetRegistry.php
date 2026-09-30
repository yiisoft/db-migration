<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration;

use InvalidArgumentException;
use Yiisoft\Db\Migration\Informer\MigrationInformerInterface;
use Yiisoft\Db\Migration\Runner\DownRunner;
use Yiisoft\Db\Migration\Runner\UpdateRunner;
use Yiisoft\Db\Migration\Service\Generate\CreateService;
use Yiisoft\Db\Migration\Service\MigrationService;
use Yiisoft\Injector\Injector;

use function array_keys;
use function is_string;
use function trim;

/**
 * Named database sets, in execution order. Existing command dependencies are used when no default set is configured.
 */
final class DatabaseSetRegistry
{
    /** @var array<string, DatabaseSet> */
    private readonly array $databases;

    /**
     * @param array<array-key, DatabaseSet> $databases Named sets, optionally including "default".
     */
    public function __construct(
        private readonly Injector $injector,
        private readonly MigrationInformerInterface $informer,
        array $databases = [],
    ) {
        $sets = [];
        foreach ($databases as $name => $database) {
            if (!is_string($name) || trim($name) === '') {
                throw new InvalidArgumentException('Database names must be non-empty strings.');
            }
            $sets[$name] = $database;
        }
        $this->databases = $sets;
    }

    /**
     * @return list<string>
     */
    public function getNames(): array
    {
        return array_keys(['default' => null, ...$this->databases]);
    }

    /**
     * @internal
     */
    public function createContext(string $name): ?DatabaseContext
    {
        if ($name === 'default' && !isset($this->databases[$name])) {
            return null;
        }

        $database = $this->databases[$name] ?? throw new InvalidArgumentException("Unknown database set: $name.");
        $migrator = new Migrator(
            $database->db,
            $this->informer,
            $database->historyTable,
            $database->migrationNameLimit,
            $database->maxSqlOutputLength,
        );
        $service = new MigrationService($database->db, $this->injector, $migrator);
        $service->setNewMigrationNamespace($database->newMigrationNamespace);
        $service->setNewMigrationPath($database->newMigrationPath);
        $service->setSourceNamespaces($database->sourceNamespaces);
        $service->setSourcePaths($database->sourcePaths);

        return new DatabaseContext(
            $migrator,
            $service,
            new CreateService($database->db, $database->useTablePrefix),
            new DownRunner($migrator),
            new UpdateRunner($migrator),
        );
    }
}
