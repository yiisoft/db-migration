<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Db\Migration\Helper\ConsoleHelper;
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
 * yii migrate/history     # showing the last 10 migrations
 * yii migrate/history 5   # showing the last 5 migrations
 * yii migrate/history all # showing the whole history
 * ```
 */
final class HistoryCommand extends Command
{
    private ConsoleHelper $consoleHelper;
    private MigrationService $migrationService;

    protected static $defaultName = 'migrate/history';

    public function __construct(ConsoleHelper $consoleHelper, MigrationService $migrationService)
    {
        $this->consoleHelper = $consoleHelper;
        $this->migrationService = $migrationService;

        parent::__construct();
    }

    public function configure(): void
    {
        $this
            ->setDescription('Displays the migration history.')
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Number of migrations to history.', 0)
            ->setHelp('This command displays the migration history.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->migrationService->title();
        $this->migrationService->before();

        $limit = $input->getOption('limit');

        if ($limit < 0) {
            $this->consoleHelper->io()->error("The step argument must be greater than 0.");
            $this->migrationService->dbVersion();

            return ExitCode::DATAERR;
        }

        $limit = (int) $limit;

        $migrations = $this->migrationService->getMigrationHistory($limit);

        if (empty($migrations)) {
            $this->consoleHelper->io()->warning("No migration has been done before.");

            return ExitCode::UNSPECIFIED_ERROR;
        }

        $n = count($migrations);

        if ($limit > 0) {
            $output->writeln("Showing the last $n applied " . ($n === 1 ? 'migration' : 'migrations') . ":\n");
        } else {
            $this->consoleHelper->io()->section(
                "Total $n " . ($n === 1 ? 'migration has' : 'migrations have') . " been applied before:"
            );
        }

        $output->writeln("<fg=green> >>> List migration history.</>\n");

        foreach ($migrations as $version => $time) {
            $output->writeln("\t<info>(" . date('Y-m-d H:i:s', (int) $time) . ') ' . $version . '</info>');
        }

        $output->writeln("\n");
        $this->consoleHelper->io()->success("Success.");
        $this->migrationService->dbVersion();

        return ExitCode::OK;
    }
}
