<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Db\Migration\Helper\ConsoleHelper;
use Yiisoft\Yii\Db\Migration\Service\MigrationService;
use Yiisoft\Yii\Db\Migration\Service\Migrate\DownService;
use Yiisoft\Yii\Db\Migration\Service\Migrate\UpdateService;

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
    private ConsoleHelper $consoleHelper;
    private DownService $downService;
    private MigrationService $migrationService;
    private UpdateService $updateService;

    protected static $defaultName = 'migrate/redo';

    public function __construct(
        ConsoleHelper $consoleHelper,
        DownService $downService,
        MigrationService $migrationService,
        UpdateService $updateService
    ) {
        $this->consoleHelper = $consoleHelper;
        $this->downService = $downService;
        $this->migrationService = $migrationService;
        $this->updateService = $updateService;

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
        $this->migrationService->before(static::$defaultName);

        $limit = $input->getOption('limit');

        if ($limit < 0) {
            $this->consoleHelper->io()->error("The step argument must be greater than 0.");
            $this->migrationService->dbVersion();

            return ExitCode::DATAERR;
        }

        $migrations = $this->migrationService->getMigrationHistory($limit);

        if (empty($migrations)) {
            $this->consoleHelper->io()->warning("No migration has been done before.");

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
            "\n<fg=cyan>Redo the above " . ($n === 1 ? "migration y/n: " : "migrations y/n: "),
            true
        );


        if ($helper->ask($input, $output, $question)) {
            foreach ($migrations as $migration) {
                if (!$this->downService->run($migration)) {
                    $this->consoleHelper->io()->error("Migration failed. The rest of the migrations are canceled.");

                    return ExitCode::UNSPECIFIED_ERROR;
                }
            }
            foreach (array_reverse($migrations) as $migration) {
                if (!$this->updateService->run($migration)) {
                    $this->consoleHelper->io()->error("Migration failed. The rest of the migrations are canceled.");

                    return ExitCode::UNSPECIFIED_ERROR;
                }
            }

            $output->writeln(
                "\n<info> >>> $n " . ($n === 1 ? 'migration was' : 'migrations were') . " redone.</info>\n"
            );
            $this->consoleHelper->io()->success("Migration redone successfully.");
        }

        $this->migrationService->dbVersion();

        return ExitCode::OK;
    }
}
