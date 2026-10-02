<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Db\Migration\DatabaseContext;
use Yiisoft\Db\Migration\DatabaseSetRegistry;

use function count;
use function implode;
use function in_array;

/**
 * @internal
 */
abstract class DatabaseCommand extends Command
{
    public function __construct(private readonly ?DatabaseSetRegistry $databases = null)
    {
        parent::__construct();
        $this->addOption('db', null, InputOption::VALUE_REQUIRED, 'Database configuration to use for migrations.');
    }

    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string|null $name */
        $name = $input->getOption('db');
        $names = $this->databases?->getNames() ?? ['default'];
        $multipleDatabases = count($names) > 1;
        $io = new SymfonyStyle($input, $output);

        if ($name !== null && !in_array($name, $names, true)) {
            $io->error(
                'Unknown database "' . OutputFormatter::escape($name) . '". Available databases: '
                . OutputFormatter::escape(implode(', ', $names)) . '.',
            );

            return Command::INVALID;
        }

        if ($name === null && $multipleDatabases && ($this instanceof DownCommand || $this instanceof RedoCommand)) {
            $io->error('The --db option is required when multiple databases are configured.');

            return Command::INVALID;
        }

        if (
            $name === null
            && $multipleDatabases
            && ($this instanceof UpdateCommand || $this instanceof NewCommand)
            && ($input->getOption('path') !== [] || $input->getOption('namespace') !== [])
        ) {
            $io->error('The --db option is required with --path or --namespace when multiple databases are configured.');

            return Command::INVALID;
        }

        if ($name !== null || $this instanceof CreateCommand) {
            $names = [$name ?? 'default'];
        }

        $isListing = $this instanceof NewCommand || $this instanceof HistoryCommand;
        $hasResults = false;

        foreach ($names as $database) {
            $context = $this->databases?->createContext($database);

            if ($multipleDatabases) {
                $io->section('Database: ' . OutputFormatter::escape($database));
            }

            $status = $this->executeForDatabase($input, $output, $context);
            // Listing commands return FAILURE for an empty set; other sets may still have results.
            if ($isListing && $status === Command::FAILURE) {
                continue;
            }
            if ($status !== Command::SUCCESS) {
                return $status;
            }
            $hasResults = true;
        }

        return $isListing && !$hasResults ? Command::FAILURE : Command::SUCCESS;
    }

    abstract protected function executeForDatabase(
        InputInterface $input,
        OutputInterface $output,
        ?DatabaseContext $context,
    ): int;
}
