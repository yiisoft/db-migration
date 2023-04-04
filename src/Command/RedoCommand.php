<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Db\Migration\Informer\ConsoleMigrationInformer;
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
 * yii migrate/redo     # redo the last applied migration
 * yii migrate/redo 3   # redo the last 3 applied migrations
 * yii migrate/redo all # redo all migrations
 * ```
 */
final class RedoCommand extends Command
{
    protected static $defaultName = 'migrate/redo';
    protected static $defaultDescription = 'Redoes the last few migrations.';

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

    public function configure(): void
    {
        $this
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Number of migrations to redoes.', null)
            ->setHelp('This command redoes the last few migrations.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->migrator->setIO($io);
        $this->migrationService->setIO($io);
        $this->downRunner->setIO($io);
        $this->updateRunner->setIO($io);

        $this->migrationService->before(self::$defaultName);

        $limit = filter_var($input->getOption('limit'), FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);

        if ($limit < 0) {
            $io->error('The step argument must be greater than 0.');
            $this->migrationService->databaseConnection();

            return ExitCode::DATAERR;
        }

        $migrations = $this->migrator->getHistory($limit);

        if (empty($migrations)) {
            $io->warning('No migration has been done before.');

            return ExitCode::UNSPECIFIED_ERROR;
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

        return ExitCode::OK;
    }
}
