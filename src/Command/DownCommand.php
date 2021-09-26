<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Db\Migration\Helper\ConsoleHelper;
use Yiisoft\Yii\Db\Migration\Informer\ConsoleMigrationInformer;
use Yiisoft\Yii\Db\Migration\Migrator;
use Yiisoft\Yii\Db\Migration\Service\Migrate\DownService;
use Yiisoft\Yii\Db\Migration\Service\MigrationService;

use function array_keys;
use function count;

/**
 * Downgrades the application by reverting old migrations.
 *
 * For example,
 *
 * ```
 * yii migrate/down     # revert the last migration
 * yii migrate/down 3   # revert the last 3 migrations
 * yii migrate/down all # revert all migrations
 * ```
 */
final class DownCommand extends Command
{
    private ConsoleHelper $consoleHelper;
    private DownService $downService;
    private MigrationService $migrationService;
    private Migrator $migrator;

    protected static $defaultName = 'migrate/down';

    public function __construct(
        ConsoleHelper $consoleHelper,
        DownService $downService,
        MigrationService $migrationService,
        Migrator $migrator,
        ConsoleMigrationInformer $informer
    ) {
        $this->consoleHelper = $consoleHelper;
        $this->downService = $downService;
        $this->migrationService = $migrationService;

        $this->migrator = $migrator;
        $this->migrator->setInformer($informer);

        parent::__construct();
    }

    public function configure(): void
    {
        $this
            ->setDescription('Downgrades the application by reverting old migrations.')
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Number of migrations to downgrade.', 1)
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Downgrade all migrations.')
            ->setHelp('This command downgrades the application by reverting old migrations.');
    }

    /**
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->migrationService->before(self::$defaultName);

        $limit = null;
        if (!$input->getOption('all')) {
            $limit = (int)$input->getOption('limit');
            if ($limit <= 0) {
                $io->error('The limit argument must be greater than 0.');
                $this->migrationService->dbVersion();
                return ExitCode::DATAERR;
            }
        }

        $migrations = $this->migrator->getHistory($limit);

        if (empty($migrations)) {
            $output->writeln("<fg=yellow> >>> Apply a new migration to run this command.</>\n");
            $io->warning('No migration has been done before.');

            return ExitCode::UNSPECIFIED_ERROR;
        }

        $migrations = array_keys($migrations);

        $n = count($migrations);
        $output->writeln(
            "<fg=yellow>Total $n " . ($n === 1 ? 'migration' : 'migrations') . " to be reverted:</>\n"
        );

        foreach ($migrations as $migration) {
            $output->writeln("\t<fg=yellow>$migration</>");
        }

        $reverted = 0;
        $helper = $this->getHelper('question');

        $question = new ConfirmationQuestion(
            "\n<fg=cyan>Revert the above " . ($n === 1 ? 'migration y/n: ' : 'migrations y/n: '),
            true
        );

        if ($helper->ask($input, $output, $question)) {
            foreach ($migrations as $migration) {
                if (!$this->downService->run($migration, $io)) {
                    $output->writeln(
                        "<fg=red>\n$reverted from $n " . ($reverted === 1 ? 'migration was' : 'migrations were') .
                        ' reverted.</>'
                    );
                    $output->writeln(
                        "<fg=red>\nMigration failed. The rest of the migrations are canceled.</>"
                    );

                    return ExitCode::UNSPECIFIED_ERROR;
                }
                $reverted++;
            }

            $output->writeln(
                "\n<fg=green> >>> [OK] $n " . ($n === 1 ? 'migration was' : 'migrations were') . " reverted.\n"
            );
            $io->success('Migrated down successfully.');
        }

        $this->migrationService->dbVersion();

        return ExitCode::OK;
    }
}
