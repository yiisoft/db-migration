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
use Yiisoft\Db\Migration\DatabaseContext;
use Yiisoft\Db\Migration\DatabaseSetRegistry;
use Yiisoft\Db\Migration\Migrator;
use Yiisoft\Db\Migration\Service\MigrationService;

use function array_keys;
use function array_map;
use function array_slice;
use function in_array;
use function preg_match;
use function str_replace;
use function strlen;
use function trim;

/**
 * Moves migration history to a version without executing up() or down().
 */
#[AsCommand('migrate:mark', 'Modifies migration history without executing migrations. WARNING: Use only if you understand the consequences; incorrect history can cause data loss.')]
final class MarkCommand extends DatabaseCommand
{
    public const BASE_MIGRATION = 'm000000_000000_base';

    public function __construct(
        private readonly MigrationService $migrationService,
        private readonly Migrator $migrator,
        ?DatabaseSetRegistry $databases = null,
    ) {
        parent::__construct($databases);
    }

    protected function configure(): void
    {
        $this
            ->addArgument('version', InputArgument::REQUIRED, 'Migration class name, timestamp, or m000000_000000_base to clear history.')
            ->addOption('force-yes', 'y', InputOption::VALUE_NONE, 'Force yes to all questions.');
    }

    protected function executeForDatabase(
        InputInterface $input,
        OutputInterface $output,
        ?DatabaseContext $context,
    ): int {
        $migrator = $context->migrator ?? $this->migrator;
        $service = $context->migrationService ?? $this->migrationService;
        $io = new SymfonyStyle($input, $output);
        $migrator->setIo($io);
        $service->setIo($io);
        $service->databaseConnection();

        /** @var string $version */
        $version = $input->getArgument('version');
        $version = trim($version, '\\');
        $label = OutputFormatter::escape($version);
        $timestamp = preg_match('/^\d{6}_?\d{6}$/D', $version) === 1
            ? str_replace('_', '', $version)
            : null;

        if ($version !== self::BASE_MIGRATION && $timestamp === null
            && preg_match('/^(?:\w+\\\\)*M\d{12}.*$/D', $version) !== 1
        ) {
            $io->error('The version must be a migration class name, a migration timestamp, or ' . self::BASE_MIGRATION . '.');
            return Command::INVALID;
        }

        $add = [];
        $remove = [];
        $found = false;
        $history = array_keys($migrator->getHistory());
        $isRecordedClass = $timestamp === null && in_array(
            $version,
            array_map(static fn(string $name): string => trim($name, '\\'), $history),
            true,
        );

        // Exact recorded classes do not need source files. Timestamps still select pending targets first.
        if ($version !== self::BASE_MIGRATION && !$isRecordedClass) {
            $pending = $service->getNewMigrations();
            foreach ($pending as $i => $migration) {
                if ($this->matches($migration, $version, $timestamp)) {
                    $add = array_slice($pending, 0, $i + 1);
                    $found = true;
                    break;
                }
            }
        }

        if (!$found) {
            $history[] = self::BASE_MIGRATION;
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

        $limit = $migrator->getMigrationNameLimit();
        foreach ($add as $migration) {
            if ($limit !== null && strlen($migration) > $limit) {
                $io->error('The migration name "' . OutputFormatter::escape($migration) . '" is too long.');
                return Command::INVALID;
            }
        }

        $io->note('Only migration history will change. No migrations will be applied or reverted.');
        if ($input->getOption('force-yes') || $io->confirm("Set migration history at $label?", false)) {
            $migrator->updateHistory($add, $remove);
            $io->success("The migration history is set at $label. No actual migration was performed.");
        }

        return Command::SUCCESS;
    }

    private function matches(string $migration, string $version, ?string $timestamp): bool
    {
        if ($timestamp === null) {
            return trim($migration, '\\') === $version;
        }

        return preg_match('/(?:^|\\\\)M' . $timestamp . '[^\\\\]*$/D', $migration) === 1;
    }
}
