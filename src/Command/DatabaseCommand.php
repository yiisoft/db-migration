<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Db\Migration\DatabaseRegistry;
use Yiisoft\Db\Migration\DatabaseSetup;

use function implode;
use function in_array;
use function is_string;

/**
 * @internal
 *
 * @template TContext of object
 */
abstract class DatabaseCommand extends Command
{
    public function __construct(private readonly ?DatabaseRegistry $additionalDatabases = null)
    {
        parent::__construct();
        $this->addOption('db', null, InputOption::VALUE_REQUIRED, 'Database configuration to use for migrations.');
    }

    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $names = $this->resolveDatabaseNames($input);
        if (is_string($names)) {
            $io->error($names);
            return Command::INVALID;
        }

        $multipleDatabases = $this->isMultipleDatabases();
        $hasResults = false;

        foreach ($names as $name) {
            if (!$multipleDatabases || $name === DatabaseRegistry::DEFAULT_DATABASE) {
                $context = $this->createDefaultContext();
            } else {
                /** @psalm-var DatabaseRegistry $this->additionalDatabases */
                $context = $this->createContext($this->additionalDatabases->createSetup($name));
            }

            if ($multipleDatabases) {
                $io->section('Database: ' . OutputFormatter::escape($name));
            }

            $status = $this->executeForDatabase($input, $output, $context);
            if ($status === null) {
                continue;
            }
            if ($status !== Command::SUCCESS) {
                return $status;
            }
            $hasResults = true;
        }

        return $hasResults ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Resolves the database names or returns a message indicating that an option is missing if multiple databases
     * are configured.
     *
     * @param InputInterface $input The input interface containing the command options and arguments.
     *
     * @return string[]|string
     * @psalm-return non-empty-list<string>|string
     */
    protected function resolveDatabaseNamesWithMissingOption(InputInterface $input): array|string
    {
        if (!$this->isMultipleDatabases()) {
            return [DatabaseRegistry::DEFAULT_DATABASE];
        }

        return 'The --db option is required when multiple databases are configured.';
    }

    /**
     * Creates the command context for the default database from the command dependencies.
     *
     * @psalm-return TContext
     */
    abstract protected function createDefaultContext(): object;

    /**
     * Creates the command context for a named database.
     *
     * @psalm-return TContext
     */
    abstract protected function createContext(DatabaseSetup $setup): object;

    /**
     * Executes the command for a single database.
     *
     * @psalm-param TContext $context
     *
     * @return int|null The command exit status, or `null` if the database has an empty result. The command fails if
     * all processed databases have empty results.
     */
    abstract protected function executeForDatabase(
        InputInterface $input,
        OutputInterface $output,
        object $context,
    ): ?int;

    /**
     * @return string[]
     * @psalm-return non-empty-list<string>
     */
    protected function getAllDatabaseNames(): array
    {
        return [DatabaseRegistry::DEFAULT_DATABASE, ...($this->additionalDatabases?->getNames() ?? [])];
    }

    protected function isMultipleDatabases(): bool
    {
        return $this->additionalDatabases !== null && $this->additionalDatabases->getNames() !== [];
    }

    /**
     * Resolves names of databases to process.
     *
     * @return string[]|string The database names, or an error message if the databases can't be resolved.
     *
     * @psalm-return non-empty-list<string>|string
     */
    private function resolveDatabaseNames(InputInterface $input): array|string
    {
        /** @var string|null $name */
        $name = $input->getOption('db');

        if ($name === null) {
            return $this->resolveDatabaseNamesWithMissingOption($input);
        }

        $availableNames = $this->getAllDatabaseNames();

        if (!in_array($name, $availableNames, true)) {
            return 'Unknown database "' . OutputFormatter::escape($name) . '". Available databases: '
                . OutputFormatter::escape(implode(', ', $availableNames)) . '.';
        }

        return [$name];
    }
}
