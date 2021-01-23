<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Command;

use Yiisoft\Yii\Db\Migration\Migrator;
use function count;
use function date;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Yii\Console\ExitCode;

use Yiisoft\Yii\Db\Migration\Helper\ConsoleHelper;
use Yiisoft\Yii\Db\Migration\Service\MigrationService;

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
    private Migrator $migrator;

    protected static $defaultName = 'migrate/history';

    public function __construct(
        ConsoleHelper $consoleHelper,
        MigrationService $migrationService,
        Migrator $migrator
    ) {
        $this->consoleHelper = $consoleHelper;
        $this->migrationService = $migrationService;
        $this->migrator = $migrator;

        parent::__construct();
    }

    public function configure(): void
    {
        $this
            ->setDescription('Displays the migration history.')
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Number of migrations to history.', null)
            ->setHelp('This command displays the migration history.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->migrationService->before(self::$defaultName);

        /** @var int|null */
        $limit = $input->getOption('limit');

        if ($limit < 0) {
            $this->consoleHelper->io()->error('The step argument must be greater than 0.');
            $this->migrationService->dbVersion();

            return ExitCode::DATAERR;
        }

        $migrations = $this->migrator->getHistory($limit);

        if (empty($migrations)) {
            $this->consoleHelper->io()->warning('No migration has been done before.');

            return ExitCode::UNSPECIFIED_ERROR;
        }

        $n = count($migrations);

        if ($limit > 0) {
            $this->consoleHelper->io()->section("Last $n applied " . ($n === 1 ? 'migration' : 'migrations') . ':');
        } else {
            $this->consoleHelper->io()->section(
                "Total $n " . ($n === 1 ? 'migration has' : 'migrations have') . ' been applied before:'
            );
        }

        foreach ($migrations as $version => $time) {
            $output->writeln("\t<info>(" . date('Y-m-d H:i:s', (int)$time) . ') ' . $version . '</info>');
        }

        $output->writeln("\n");
        $this->consoleHelper->io()->success('Success.');
        $this->migrationService->dbVersion();

        return ExitCode::OK;
    }
}
