<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration;

use InvalidArgumentException;
use Yiisoft\Db\Migration\Command\CreateCommand;
use Yiisoft\Db\Migration\Command\DatabaseCommand;
use Yiisoft\Db\Migration\Command\DownCommand;
use Yiisoft\Db\Migration\Command\HistoryCommand;
use Yiisoft\Db\Migration\Command\NewCommand;
use Yiisoft\Db\Migration\Command\RedoCommand;
use Yiisoft\Db\Migration\Command\UpdateCommand;
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
 * Additional database sets, in execution order. The existing command dependencies form the default set.
 */
final class DatabaseSetRegistry
{
    /** @var array<string, DatabaseSet> */
    private readonly array $databases;

    /**
     * @param array<array-key, DatabaseSet> $databases Additional sets. The name "default" is reserved.
     */
    public function __construct(
        private readonly Injector $injector,
        private readonly MigrationInformerInterface $informer,
        array $databases = [],
    ) {
        $sets = [];
        foreach ($databases as $name => $database) {
            if (!is_string($name) || trim($name) === '' || $name === 'default') {
                throw new InvalidArgumentException('Database names must be non-empty strings other than "default".');
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
        return ['default', ...array_keys($this->databases)];
    }

    /**
     * @internal
     * @param class-string<DatabaseCommand> $class
     */
    public function createCommand(string $name, string $class): DatabaseCommand
    {
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

        return match ($class) {
            CreateCommand::class => new CreateCommand(new CreateService($database->db, $database->useTablePrefix), $service, $migrator),
            DownCommand::class => new DownCommand(new DownRunner($migrator), $service, $migrator),
            HistoryCommand::class => new HistoryCommand($service, $migrator),
            NewCommand::class => new NewCommand($service, $migrator),
            RedoCommand::class => new RedoCommand($service, $migrator, new DownRunner($migrator), new UpdateRunner($migrator)),
            UpdateCommand::class => new UpdateCommand(new UpdateRunner($migrator), $service, $migrator),
            default => throw new InvalidArgumentException("Unsupported migration command: $class."),
        };
    }
}
