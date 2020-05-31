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

use function array_slice;
use function count;

/**
 * Displays the un-applied new migrations.
 *
 * This command will show the new migrations that have not been applied.
 * For example,
 *
 * ```
 * yii migrate/new     # showing the first 10 new migrations
 * yii migrate/new 5   # showing the first 5 new migrations
 * yii migrate/new all # showing all new migrations
 * ```
 */
final class NewCommand extends Command
{
    private ConsoleHelper $consoleHelper;
    private MigrationService $migrationService;

    protected static $defaultName = 'migrate/new';

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
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Number of migrations to history.', 10)
            ->setHelp('This command displays the migration history.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->migrationService->before(static::$defaultName);

        $limit = $input->getOption('limit');

        $limit = (int) $limit;

        if ($limit < 0) {
            $this->consoleHelper->io()->error("The step argument must be greater than 0.");
            $this->migrationService->dbVersion();

            return ExitCode::DATAERR;
        }

        $migrations = $this->migrationService->getNewMigrations();

        if (empty($migrations)) {
            $this->consoleHelper->io()->success("No new migrations found. Your system is up-to-date.");
            $this->migrationService->dbVersion();

            return ExitCode::UNSPECIFIED_ERROR;
        }

        $n = count($migrations);

        if ($limit && $n > $limit) {
            $migrations = array_slice($migrations, 0, $limit);
            $this->consoleHelper->io()->warning(
                "Showing $limit out of $n new " . ($n === 1 ? 'migration' : 'migrations') . ":\n"
            );
        } else {
            $this->consoleHelper->io()->section("Found $n new " . ($n === 1 ? 'migration' : 'migrations') . ":");
        }

        foreach ($migrations as $migration) {
            $output->writeln("<info>\t" . $migration . "</info>");
        }

        $this->migrationService->dbVersion();

        return ExitCode::OK;
    }
}
