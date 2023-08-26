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
use Yiisoft\Yii\Db\Migration\Informer\ConsoleMigrationInformer;
use Yiisoft\Yii\Db\Migration\Migrator;
use Yiisoft\Yii\Db\Migration\Runner\DownRunner;
use Yiisoft\Yii\Db\Migration\Service\MigrationService;

use function array_keys;
use function count;

/**
 * Reverts the specified number of latest migrations.
 *
 * For example,
 *
 * ```
 * yii migrate:down     # revert the last migration
 * yii migrate:down 3   # revert the last 3 migrations
 * yii migrate:down all # revert all migrations
 * ```
 */
#[AsCommand('migrate:down', 'Reverts the specified number of latest migrations.')]
final class DownCommand extends Command
{
    public function __construct(
        private DownRunner $downRunner,
        private MigrationService $migrationService,
        private Migrator $migrator,
        ConsoleMigrationInformer $informer
    ) {
        $this->migrator->setInformer($informer);

        parent::__construct();
    }

    public function configure(): void
    {
        $this
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Number of migrations to revert.', 1)
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Revert all migrations.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->migrator->setIO($io);
        $this->migrationService->setIO($io);
        $this->downRunner->setIO($io);

        $this->migrationService->before(self::getDefaultName() ?? '');

        $limit = null;
        if (!$input->getOption('all')) {
            $limit = (int)$input->getOption('limit');
            if ($limit <= 0) {
                $io->error('The limit argument must be greater than 0.');
                $this->migrationService->databaseConnection();
                return Command::INVALID;
            }
        }

        $migrations = $this->migrator->getHistory($limit);

        if (empty($migrations)) {
            $output->writeln("<fg=yellow> >>> Apply at least one migration first.</>\n");
            $io->warning('No migration has been done before.');

            return Command::FAILURE;
        }

        $migrations = array_keys($migrations);

        $n = count($migrations);
        $output->writeln(
            "<fg=yellow>Total $n " . ($n === 1 ? 'migration' : 'migrations') . " to be reverted:</>\n"
        );

        foreach ($migrations as $migration) {
            $output->writeln("\t<fg=yellow>$migration</>");
        }

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $question = new ConfirmationQuestion(
            "\n<fg=cyan>Revert the above " . ($n === 1 ? 'migration y/n: ' : 'migrations y/n: '),
            true
        );

        if ($helper->ask($input, $output, $question)) {
            /** @psalm-var class-string[] $migrations */
            $instances = $this->migrationService->makeRevertibleMigrations($migrations);
            foreach ($instances as $instance) {
                $this->downRunner->run($instance);
            }

            $output->writeln(
                "\n<fg=green> >>> [OK] $n " . ($n === 1 ? 'migration was' : 'migrations were') . " reverted.\n"
            );
            $io->success('Migrated down successfully.');
        }

        $this->migrationService->databaseConnection();

        return Command::SUCCESS;
    }
}
