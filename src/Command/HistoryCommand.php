<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Yii\Db\Migration\Migrator;
use Yiisoft\Yii\Db\Migration\Service\MigrationService;

use function count;
use function date;

/**
 * Displays the migration history.
 *
 * This command will show the list of migrations that have been applied
 * so far. For example,
 *
 * ```
 * yii migrate:history     # last 10 migrations
 * yii migrate:history 5   # last 5 migrations
 * yii migrate:history all # whole history
 * ```
 */
#[AsCommand('migrate:history', 'Displays the migration history.')]
final class HistoryCommand extends Command
{
    public function __construct(
        private MigrationService $migrationService,
        private Migrator $migrator
    ) {
        parent::__construct();
    }

    public function configure(): void
    {
        $this->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Maximum number of migrations to display.', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->migrator->setIO($io);
        $this->migrationService->setIO($io);

        $this->migrationService->before(self::getDefaultName() ?? '');

        $limit = filter_var($input->getOption('limit'), FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);

        if ($limit < 0) {
            $io->error('The step argument must be greater than 0.');
            $this->migrationService->databaseConnection();

            return Command::INVALID;
        }

        $migrations = $this->migrator->getHistory($limit);

        if (empty($migrations)) {
            $io->warning('No migration has been done before.');

            return Command::FAILURE;
        }

        $n = count($migrations);

        if ($limit > 0) {
            $io->section("Last $n applied " . ($n === 1 ? 'migration' : 'migrations') . ':');
        } else {
            $io->section(
                "Total $n " . ($n === 1 ? 'migration has' : 'migrations have') . ' been applied before:'
            );
        }

        foreach ($migrations as $version => $time) {
            $output->writeln("\t<info>(" . date('Y-m-d H:i:s', (int) $time) . ') ' . $version . '</info>');
        }

        $output->writeln("\n");
        $io->success('Success.');
        $this->migrationService->databaseConnection();

        return Command::SUCCESS;
    }
}
