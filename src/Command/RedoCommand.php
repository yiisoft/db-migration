<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Command;

use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Yii\Db\Migration\Informer\ConsoleMigrationInformer;
use Yiisoft\Yii\Db\Migration\Migrator;
use function array_keys;
use function array_reverse;
use function count;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Db\Migration\Helper\ConsoleHelper;

use Yiisoft\Yii\Db\Migration\Service\Migrate\DownService;
use Yiisoft\Yii\Db\Migration\Service\Migrate\UpdateService;
use Yiisoft\Yii\Db\Migration\Service\MigrationService;

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
    private ConsoleHelper $consoleHelper;
    private DownService $downService;
    private MigrationService $migrationService;
    private Migrator $migrator;
    private UpdateService $updateService;

    protected static $defaultName = 'migrate/redo';

    public function __construct(
        ConsoleHelper $consoleHelper,
        DownService $downService,
        MigrationService $migrationService,
        Migrator $migrator,
        ConsoleMigrationInformer $informer,
        UpdateService $updateService
    ) {
        $this->consoleHelper = $consoleHelper;
        $this->downService = $downService;
        $this->migrationService = $migrationService;
        $this->updateService = $updateService;

        $this->migrator = $migrator;
        $this->migrator->setInformer($informer);

        parent::__construct();
    }

    public function configure(): void
    {
        $this
            ->setDescription('Redoes the last few migrations.')
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Number of migrations to redoes.', null)
            ->setHelp('This command redoes the last few migrations.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->migrationService->before(self::$defaultName);

        $limit = filter_var($input->getOption('limit'), FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);

        if ($limit < 0) {
            $io->error('The step argument must be greater than 0.');
            $this->migrationService->dbVersion();

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

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion(
            "\n<fg=cyan>Redo the above " . ($n === 1 ? 'migration y/n: ' : 'migrations y/n: '),
            true
        );


        if ($helper->ask($input, $output, $question)) {
            foreach ($migrations as $migration) {
                if (!$this->downService->run($migration, $io)) {
                    $io->error('Migration failed. The rest of the migrations are canceled.');

                    return ExitCode::UNSPECIFIED_ERROR;
                }
            }
            foreach (array_reverse($migrations) as $migration) {
                if (!$this->updateService->run($migration, $io)) {
                    $io->error('Migration failed. The rest of the migrations are canceled.');

                    return ExitCode::UNSPECIFIED_ERROR;
                }
            }

            $output->writeln(
                "\n<info> >>> $n " . ($n === 1 ? 'migration was' : 'migrations were') . " redone.</info>\n"
            );
            $io->success('Migration redone successfully.');
        }

        $this->migrationService->dbVersion();

        return ExitCode::OK;
    }
}
