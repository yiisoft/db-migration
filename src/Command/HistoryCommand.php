<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Db\Migration\DatabaseContext;
use Yiisoft\Db\Migration\DatabaseSetRegistry;
use Yiisoft\Db\Migration\Migrator;
use Yiisoft\Db\Migration\Service\MigrationService;

use function count;
use function date;

/**
 * Displays the migration history.
 *
 * This command will show the list of migrations that have been applied so far.
 *
 * For example:
 *
 * ```shell
 * ./yii migrate:history           # last 10 migrations
 * ./yii migrate:history --limit=5 # last 5 migrations
 * ./yii migrate:history --all     # whole history
 * ```
 */
#[AsCommand('migrate:history', 'Displays the migration history.')]
final class HistoryCommand extends DatabaseCommand
{
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
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Number of migrations to display.', 10)
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'All migrations.');
    }

    protected function executeForDatabase(
        InputInterface $input,
        OutputInterface $output,
        ?DatabaseContext $context,
    ): int {
        $migrator = $context->migrator ?? $this->migrator;
        $migrationService = $context->migrationService ?? $this->migrationService;

        $io = new SymfonyStyle($input, $output);
        $migrator->setIo($io);
        $migrationService->setIo($io);

        $migrationService->databaseConnection();

        $migrationService->before($this->getName() ?? '');

        $limit = !$input->getOption('all')
            ? (int) $input->getOption('limit')
            : null;

        if ($limit !== null && $limit <= 0) {
            $io->error('The limit option must be greater than 0.');

            return Command::INVALID;
        }

        $migrations = $migrator->getHistory($limit);

        if (empty($migrations)) {
            $io->warning('No migration has been done before.');

            return Command::FAILURE;
        }

        $countMigrations = count($migrations);

        if ($limit === $countMigrations) {
            $io->section("Last $countMigrations applied " . ($countMigrations === 1 ? 'migration' : 'migrations') . ':');
        } else {
            $io->section("Total $countMigrations " . ($countMigrations === 1 ? 'migration has' : 'migrations have') . ' been applied before:');
        }

        foreach ($migrations as $version => $time) {
            $output->writeln("\t<info>(" . date('Y-m-d H:i:s', (int) $time) . ') ' . $version . '</info>');
        }

        $output->writeln("\n");
        $io->success('Success.');

        return Command::SUCCESS;
    }
}
