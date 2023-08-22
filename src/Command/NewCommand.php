<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Yii\Db\Migration\Service\MigrationService;

use function array_slice;
use function count;

/**
 * Displays the un-applied new migrations.
 *
 * This command will show the new migrations that have not been applied.
 * For example,
 *
 * ```
 * yii migrate:new     # showing the first 10 new migrations
 * yii migrate:new 5   # showing the first 5 new migrations
 * yii migrate:new all # showing all new migrations
 * ```
 */
#[AsCommand('migrate:new', 'Displays the first 10 new migrations.')]
final class NewCommand extends Command
{
    public function __construct(private MigrationService $migrationService)
    {
        parent::__construct();
    }

    public function configure(): void
    {
        $this
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Number of migrations to history.', '10');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->migrationService->setIO($io);

        $this->migrationService->before(self::getDefaultName() ?? '');

        $limit = (int) $input->getOption('limit');

        if ($limit < 0) {
            $io->error('The step argument must be greater than 0.');
            $this->migrationService->databaseConnection();

            return Command::INVALID;
        }

        /** @psalm-var class-string[] $migrations */
        $migrations = $this->migrationService->getNewMigrations();

        if (empty($migrations)) {
            $io->warning('No new migrations found. Your system is up-to-date.');
            $this->migrationService->databaseConnection();

            return Command::FAILURE;
        }

        $n = count($migrations);

        if ($limit && $n > $limit) {
            $migrations = array_slice($migrations, 0, $limit);
            $io->warning(
                "Showing $limit out of $n new " . ($n === 1 ? 'migration' : 'migrations') . ":\n"
            );
        } else {
            $io->section("Found $n new " . ($n === 1 ? 'migration' : 'migrations') . ':');
        }

        foreach ($migrations as $migration) {
            $output->writeln("<info>\t" . $migration . '</info>');
        }

        $this->migrationService->databaseConnection();

        return Command::SUCCESS;
    }
}
