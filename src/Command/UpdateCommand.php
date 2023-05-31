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
use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Db\Migration\Informer\ConsoleMigrationInformer;
use Yiisoft\Yii\Db\Migration\Migrator;
use Yiisoft\Yii\Db\Migration\Runner\UpdateRunner;
use Yiisoft\Yii\Db\Migration\Service\MigrationService;

use function array_slice;
use function count;
use function strlen;

/**
 * Upgrades the application by applying new migrations.
 *
 * For example,
 *
 * ```
 * yii migrate:up           # apply all new migrations
 * yii migrate:up --limit=3 # apply the first 3 new migrations
 * ```
 */
#[AsCommand('migrate:up', 'Upgrades the application by applying new migrations.')]
final class UpdateCommand extends Command
{
    public function __construct(
        private UpdateRunner $updateRunner,
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
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Number of migrations to apply.', '0')
            ->setHelp('This command applying new migrations to database.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->migrator->setIO($io);
        $this->migrationService->setIO($io);
        $this->updateRunner->setIO($io);

        if ($this->migrationService->before(self::getDefaultName() ?? '') === ExitCode::DATAERR) {
            return ExitCode::DATAERR;
        }

        $limit = (int) $input->getOption('limit');

        $migrations = $this->migrationService->getNewMigrations();

        if (empty($migrations)) {
            $output->writeln("<fg=green> >>> No new migrations found.</>\n");
            $io->success('Your system is up-to-date.');
            $this->migrationService->databaseConnection();

            return ExitCode::OK;
        }

        $total = count($migrations);

        if ($limit > 0) {
            $migrations = array_slice($migrations, 0, $limit);
        }

        $n = count($migrations);

        if ($n === $total) {
            $output->writeln(
                "<fg=yellow>Total $n new " . ($n === 1 ? 'migration' : 'migrations') . ' to be ' .
                "applied:</>\n"
            );
        } else {
            $output->writeln(
                "<fg=yellow>Total $n out of $total new " . ($total === 1 ? 'migration' : 'migrations') .
                " to be applied:</>\n"
            );
        }

        foreach ($migrations as $migration) {
            $nameLimit = $this->migrator->getMigrationNameLimit();

            if (strlen($migration) > $nameLimit) {
                $output->writeln(
                    "\n<fg=red>The migration name '$migration' is too long. Its not possible to apply " .
                    'this migration.</>'
                );

                return ExitCode::UNSPECIFIED_ERROR;
            }

            $output->writeln("\t<fg=yellow>$migration</>");
        }

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $question = new ConfirmationQuestion(
            "\n<fg=cyan>Apply the above " . ($n === 1 ? 'migration y/n: ' : 'migrations y/n: '),
            true
        );

        if ($helper->ask($input, $output, $question)) {
            $instances = $this->migrationService->makeMigrations($migrations);
            foreach ($instances as $instance) {
                $this->updateRunner->run($instance);
            }

            $output->writeln(
                "\n<fg=green> >>> $n " . ($n === 1 ? 'Migration was' : 'Migrations were') . " applied.</>\n"
            );
            $io->success('Updated successfully.');
        }

        $this->migrationService->databaseConnection();

        return ExitCode::OK;
    }
}
