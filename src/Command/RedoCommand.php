<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Db\Migration\Informer\ConsoleMigrationInformer;
use Yiisoft\Db\Migration\Migrator;
use Yiisoft\Db\Migration\Runner\DownRunner;
use Yiisoft\Db\Migration\Runner\UpdateRunner;
use Yiisoft\Db\Migration\Service\MigrationService;

use function array_keys;
use function array_reverse;
use function count;

/**
 * Redoes the last few migrations.
 *
 * This command will first revert the specified migrations, and then apply them again.
 *
 * For example:
 *
 * ```shell
 * ./yii migrate:redo           # redo the last applied migration
 * ./yii migrate:redo --limit=3 # redo last 3 applied migrations
 * ./yii migrate:redo --all     # redo all migrations
 * ```
 */
#[AsCommand('migrate:redo', 'Redoes the last few migrations.')]
final class RedoCommand extends Command
{
    public function __construct(
        private MigrationService $migrationService,
        private Migrator $migrator,
        ConsoleMigrationInformer $informer,
        private DownRunner $downRunner,
        private UpdateRunner $updateRunner
    ) {
        $this->migrator->setInformer($informer);

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Number of migrations to redo.', 1)
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'All migrations.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->migrator->setIO($io);
        $this->migrationService->setIO($io);
        $this->downRunner->setIO($io);
        $this->updateRunner->setIO($io);

        $this->migrationService->before(self::getDefaultName() ?? '');

        $limit = !$input->getOption('all')
            ? (int)$input->getOption('limit')
            : null;

        if ($limit !== null && $limit <= 0) {
            $io->error('The limit option must be greater than 0.');
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
        $question = new ConfirmationQuestion(
            "\n<fg=cyan>Redo the above " . ($n === 1 ? 'migration y/n: ' : 'migrations y/n: '),
            true
        );

        if ($helper->ask($input, $output, $question)) {
            /** @psalm-var class-string[] $migrations */
            $instances = $this->migrationService->makeRevertibleMigrations($migrations);
            foreach ($instances as $instance) {
                $this->downRunner->run($instance);
            }
            foreach (array_reverse($instances) as $instance) {
                $this->updateRunner->run($instance);
            }

            $output->writeln(
                "\n<info> >>> $n " . ($n === 1 ? 'migration was' : 'migrations were') . " redone.</info>\n"
            );
            $io->success('Migration redone successfully.');
        }

        $this->migrationService->databaseConnection();

        return Command::SUCCESS;
    }
}
