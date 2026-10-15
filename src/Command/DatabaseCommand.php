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

use function count;
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
    public function __construct(private readonly ?DatabaseRegistry $databases = null)
    {
        parent::__construct();
        $this->addOption('db', null, InputOption::VALUE_REQUIRED, 'Database configuration to use for migrations.');
    }

    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $availableNames = [DatabaseRegistry::DEFAULT_DATABASE, ...($this->databases?->getNames() ?? [])];
        $multipleDatabases = count($availableNames) > 1;
        $io = new SymfonyStyle($input, $output);

        $names = $this->resolveDatabaseNames($input, $availableNames, $multipleDatabases);
        if (is_string($names)) {
            $io->error($names);
            return Command::INVALID;
        }

        $hasResults = false;

        foreach ($names as $name) {
            if ($this->databases === null || $name === DatabaseRegistry::DEFAULT_DATABASE) {
                $context = $this->createDefaultContext();
            } else {
                $context = $this->createContext($this->databases->createSetup($name));
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
     * Returns how the command behaves when the `--db` option isn't specified.
     */
    abstract protected function getMissingDatabaseOptionPolicy(InputInterface $input): MissingDatabaseOptionPolicy;

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
     * Resolves names of databases to process.
     *
     * @param string[] $availableNames
     * @return string[]|string The database names, or an error message if the databases can't be resolved.
     *
     * @psalm-param list<string> $availableNames
     * @psalm-return list<string>|string
     */
    private function resolveDatabaseNames(
        InputInterface $input,
        array $availableNames,
        bool $multipleDatabases,
    ): array|string {
        /** @var string|null $name */
        $name = $input->getOption('db');

        if ($name === null) {
            $policy = $this->getMissingDatabaseOptionPolicy($input);
            if ($multipleDatabases) {
                $error = match ($policy) {
                    MissingDatabaseOptionPolicy::RejectIfMultiple => 'The --db option is required when multiple databases are configured.',
                    MissingDatabaseOptionPolicy::RejectIfMultipleWithFilter => 'The --db option is required with --path or --namespace when multiple databases are configured.',
                    MissingDatabaseOptionPolicy::ProcessDefaultDatabase,
                    MissingDatabaseOptionPolicy::ProcessAllDatabases => null,
                };
                if ($error !== null) {
                    return $error;
                }
            }

            return $policy === MissingDatabaseOptionPolicy::ProcessDefaultDatabase
                ? [DatabaseRegistry::DEFAULT_DATABASE]
                : $availableNames;
        }

        if (!in_array($name, $availableNames, true)) {
            return 'Unknown database "' . OutputFormatter::escape($name) . '". Available databases: '
                . OutputFormatter::escape(implode(', ', $availableNames)) . '.';
        }

        return [$name];
    }
}
