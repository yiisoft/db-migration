<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Db\Migration\Command\Context\MigrationContext;
use Yiisoft\Db\Migration\DatabaseRegistry;
use Yiisoft\Db\Migration\DatabaseSetup;
use Yiisoft\Db\Migration\Migrator;
use Yiisoft\Db\Migration\Service\MigrationService;

use function array_keys;
use function array_map;
use function array_slice;
use function count;
use function in_array;
use function preg_match;
use function strlen;
use function trim;

/**
 * Moves migration history to a version without executing up() or down().
 *
 * Use this command only after verifying that the database schema already matches the target migration state.
 *
 * For example,
 *
 * ```shell
 * ./yii migrate:mark 'App\Migrations\M260101000002CreateIndex' # record pending migrations through this class
 * ./yii migrate:mark M260101000003AddColumn                    # use an unqualified migration class name
 * ./yii migrate:mark 260101000004                              # select a migration by its timestamp
 * ./yii migrate:mark --all                                     # remove all migration history entries
 * ```
 *
 * A pending target is recorded along with all earlier pending migrations. A recorded target removes newer history
 * entries. Use the full class name when several migrations share a timestamp.
 *
 * @extends DatabaseCommand<MigrationContext>
 */
#[AsCommand('migrate:mark', 'Modifies migration history without executing migrations. WARNING: Use only if you understand the consequences; incorrect history can cause data loss.')]
final class MarkCommand extends DatabaseCommand
{
    public function __construct(
        private readonly MigrationService $migrationService,
        private readonly Migrator $migrator,
        ?DatabaseRegistry $additionalDatabases = null,
    ) {
        parent::__construct($additionalDatabases);
    }

    protected function configure(): void
    {
        $this
            ->addArgument('version', InputArgument::OPTIONAL, 'Migration class name or timestamp.')
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Remove all migration history entries.')
            ->addOption('force-yes', 'y', InputOption::VALUE_NONE, 'Force yes to all questions.');
    }

    protected function createDefaultContext(): MigrationContext
    {
        return new MigrationContext($this->migrator, $this->migrationService);
    }

    protected function createContext(DatabaseSetup $setup): MigrationContext
    {
        return new MigrationContext($setup->migrator, $setup->migrationService);
    }

    protected function executeForDatabase(
        InputInterface $input,
        OutputInterface $output,
        object $context,
    ): int {
        $io = new SymfonyStyle($input, $output);
        $context->migrator->setIo($io);
        $context->migrationService->setIo($io);
        $context->migrationService->databaseConnection();

        /** @var string|null $version */
        $version = $input->getArgument('version');
        $all = (bool) $input->getOption('all');

        if ($all === ($version !== null)) {
            $io->error('Specify either a version or the "--all" option.');
            return Command::INVALID;
        }

        $history = array_keys($context->migrator->getHistory());

        if ($version === null) {
            if ($history === []) {
                $io->success('The migration history is already empty. Nothing needs to be done.');
                return Command::SUCCESS;
            }

            $this->writeMigrations($output, $history, 'to be removed from history');

            $io->note('Only migration history will change. No migrations will be reverted.');
            if ($input->getOption('force-yes') || $io->confirm('Remove all migration history entries?', false)) {
                $context->migrator->updateHistory([], $history);
                $io->success('The migration history is cleared. No actual migration was performed.');
            }

            return Command::SUCCESS;
        }

        $version = trim($version, '\\');
        $label = OutputFormatter::escape($version);
        $timestamp = preg_match('/^\d{12}$/D', $version) === 1 ? $version : null;

        if ($timestamp === null && preg_match('/^(?:\w+\\\\)*M\d{12}.*$/D', $version) !== 1) {
            $io->error('The version must be a migration class name or a migration timestamp.');
            return Command::INVALID;
        }

        $add = [];
        $remove = [];
        $found = false;
        $isRecordedClass = $timestamp === null && in_array(
            $version,
            array_map(static fn(string $name): string => trim($name, '\\'), $history),
            true,
        );

        // Exact recorded classes do not need source files. Timestamps still select pending targets first.
        if (!$isRecordedClass) {
            $pending = $context->migrationService->getNewMigrations();
            foreach ($pending as $i => $migration) {
                if ($this->matches($migration, $version, $timestamp)) {
                    $add = array_slice($pending, 0, $i + 1);
                    $found = true;
                    break;
                }
            }
        }

        if (!$found) {
            foreach ($history as $i => $migration) {
                if ($this->matches($migration, $version, $timestamp)) {
                    $remove = array_slice($history, 0, $i);
                    $found = true;
                    break;
                }
            }
        }

        if (!$found) {
            $io->error("Unable to find the version '$label'.");
            return Command::INVALID;
        }

        if ($add === [] && $remove === []) {
            $io->success("Already at '$label'. Nothing needs to be done.");
            return Command::SUCCESS;
        }

        $limit = $context->migrator->getMigrationNameLimit();
        foreach ($add as $migration) {
            if ($limit !== null && strlen($migration) > $limit) {
                $io->error('The migration name "' . OutputFormatter::escape($migration) . '" is too long.');
                return Command::INVALID;
            }
        }

        $this->writeMigrations($output, $add, 'to be marked as applied');
        $this->writeMigrations($output, $remove, 'to be removed from history');

        $io->note('Only migration history will change. No migrations will be applied or reverted.');
        if ($input->getOption('force-yes') || $io->confirm("Set migration history at $label?", false)) {
            $context->migrator->updateHistory($add, $remove);
            $io->success("The migration history is set at $label. No actual migration was performed.");
        }

        return Command::SUCCESS;
    }

    /**
     * @param string[] $migrations
     */
    private function writeMigrations(OutputInterface $output, array $migrations, string $action): void
    {
        if ($migrations === []) {
            return;
        }

        $count = count($migrations);
        $migrationWord = $count === 1 ? 'migration' : 'migrations';

        $output->writeln("<fg=yellow>Total $count $migrationWord $action:</>\n");

        foreach ($migrations as $i => $migration) {
            $output->writeln("\t<fg=yellow>" . ($i + 1) . '. ' . OutputFormatter::escape($migration) . '</>');
        }

        $output->writeln('');
    }

    private function matches(string $migration, string $version, ?string $timestamp): bool
    {
        if ($timestamp === null) {
            return trim($migration, '\\') === $version;
        }

        return preg_match('/(?:^|\\\\)M' . $timestamp . '[^\\\\]*$/D', $migration) === 1;
    }
}
