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

    protected static $defaultName = 'migrate/down';

    public function __construct(
        ConsoleHelper $consoleHelper,
        DownService $downService,
        MigrationService $migrationService
    ) {
        $this->consoleHelper = $consoleHelper;
        $this->downService = $downService;
        $this->migrationService = $migrationService;

        parent::__construct();
    }

    public function configure(): void
    {
        $this
            ->setDescription('Downgrades the application by reverting old migrations.')
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Number of migrations to downgrade.', null)
            ->setHelp('This command downgrades the application by reverting old migrations.');
    }

    /**
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->migrationService->before(static::$defaultName);

        /** @var int|null */
        $limit = $input->getOption('limit');

        if ($limit < 0) {
            $this->consoleHelper->io()->error("The step argument must be greater than 0.");
            $this->migrationService->dbVersion();

            return ExitCode::DATAERR;
        }

        $migrations = $this->migrationService->getMigrationHistory($limit);

        if (empty($migrations)) {
            $output->writeln("<fg=yellow> >>> Apply a new migration to run this command.</>\n");
            $this->consoleHelper->io()->warning("No migration has been done before.");

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
            "\n<fg=cyan>Revert the above " . ($n === 1 ? "migration y/n: " : "migrations y/n: "),
            true
        );

        if ($helper->ask($input, $output, $question)) {
            foreach ($migrations as $migration) {
                if (!$this->downService->run($migration)) {
                    $output->writeln(
                        "<fg=red>\n$reverted from $n " . ($reverted === 1 ? 'migration was' : 'migrations were') .
                        " reverted.</>"
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
            $this->consoleHelper->io()->success("Migrated down successfully.");
        }

        $this->migrationService->dbVersion();

        return ExitCode::OK;
    }
}
