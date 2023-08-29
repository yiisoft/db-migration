<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Yii\Db\Migration\Migrator;
use Yiisoft\Yii\Db\Migration\Runner\DownRunner;
use Yiisoft\Yii\Db\Migration\Runner\UpdateRunner;
use Yiisoft\Yii\Db\Migration\Service\MigrationService;

use function array_keys;
use function array_reverse;
use function count;

/**
 * Redoes the last few migrations.
 *
 * This command will first revert the specified migrations, and then apply
 * them again. For example,
 *
 * ```
 * yii migrate:redo     # redo the last applied migration
 * yii migrate:redo 3   # redo last 3 applied migrations
 * yii migrate:redo all # redo all migrations
 * ```
 */
#[AsCommand('migrate:redo', 'Redoes the last few migrations.')]
final class RedoCommand extends Command
{
    public function __construct(
        private MigrationService $migrationService,
        private Migrator $migrator,
        private DownRunner $downRunner,
        private UpdateRunner $updateRunner
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Number of migrations to redo.', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->migrationService->setIO($io);
        $this->downRunner->setIO($io);
        $this->updateRunner->setIO($io);
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

        $migrations = array_keys($migrations);

        $n = count($migrations);
        $output->writeln("<warning>Total $n " . ($n === 1 ? 'migration' : 'migrations') . " to be redone:</warning>\n");

        foreach ($migrations as $migration) {
            $output->writeln("\t<info>$migration</info>");
        }

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $migrationWord = $n === 1 ? 'migration' : 'migrations';

        $question = new ConfirmationQuestion("\n<fg=cyan>Redo the above $migrationWord", true);

        if ($helper->ask($input, $output, $question)) {
            /** @psalm-var class-string[] $migrations */
            $instances = $this->migrationService->makeRevertibleMigrations($migrations);
            foreach ($instances as $instance) {
                $this->downRunner->run($instance);
            }
            foreach (array_reverse($instances) as $instance) {
                $this->updateRunner->run($instance);
            }

            $migrationWord = $n === 1 ? 'migration was' : 'migrations were';

            $output->writeln("\n<info> >>> $n $migrationWord redone.</info>\n");
            $io->success('Migration redone successfully.');
        }

        $this->migrationService->databaseConnection();

        return Command::SUCCESS;
    }
}
