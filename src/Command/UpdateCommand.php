<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Command;

use Yiisoft\Yii\Db\Migration\Informer\ConsoleMigrationInformer;
use Yiisoft\Yii\Db\Migration\Migrator;
use function array_slice;
use function count;
use function strlen;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Yiisoft\Yii\Console\ExitCode;

use Yiisoft\Yii\Db\Migration\Helper\ConsoleHelper;
use Yiisoft\Yii\Db\Migration\Service\Migrate\UpdateService;
use Yiisoft\Yii\Db\Migration\Service\MigrationService;

/**
 * Upgrades the application by applying new migrations.
 *
 * For example,
 *
 * ```
 * yii migrate/up           # apply all new migrations
 * yii migrate/up --limit=3 # apply the first 3 new migrations
 * ```
 */
final class UpdateCommand extends Command
{
    private ConsoleHelper $consoleHelper;
    private UpdateService $updateService;
    private MigrationService $migrationService;

    protected static $defaultName = 'migrate/up';
    private Migrator $migrator;

    public function __construct(
        ConsoleHelper $consoleHelper,
        UpdateService $updateService,
        MigrationService $migrationService,
        Migrator $migrator,
        ConsoleMigrationInformer $informer
    ) {
        $this->consoleHelper = $consoleHelper;
        $this->updateService = $updateService;
        $this->migrationService = $migrationService;

        $this->migrator = $migrator;
        $this->migrator->setInformer($informer);

        parent::__construct();
    }

    public function configure(): void
    {
        $this
            ->setDescription('Upgrades the application by applying new migrations.')
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Number of migrations to apply.', 0)
            ->setHelp('This command applying new migrations to database.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->migrationService->before(self::$defaultName) === ExitCode::DATAERR) {
            return ExitCode::DATAERR;
        }

        /** @var int|null */
        $limit = $input->getOption('limit');

        $migrations = $this->migrationService->getNewMigrations();

        if (empty($migrations)) {
            $output->writeln("<fg=green> >>> No new migrations found.</>\n");
            $this->consoleHelper->io()->success('Your system is up-to-date.');
            $this->migrationService->dbVersion();

            return ExitCode::UNSPECIFIED_ERROR;
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

        $applied = 0;
        $helper = $this->getHelper('question');

        $question = new ConfirmationQuestion(
            "\n<fg=cyan>Apply the above " . ($n === 1 ? 'migration y/n: ' : 'migrations y/n: '),
            true
        );

        if ($helper->ask($input, $output, $question)) {
            foreach ($migrations as $migration) {
                if (!$this->updateService->run($migration)) {
                    $output->writeln("\n<fg=red>$applied from $n " . ($applied === 1 ? 'migration was' :
                        'migrations were') . " applied.</>\n");
                    $output->writeln("\n<fg=red>Migration failed. The rest of the migrations are canceled.</>\n");

                    return ExitCode::UNSPECIFIED_ERROR;
                }
                $applied++;
            }

            $output->writeln(
                "\n<fg=green> >>> $n " . ($n === 1 ? 'Migration was' : 'Migrations were') . " applied.</>\n"
            );
            $this->consoleHelper->io()->success('Updated successfully.');
        }

        $this->migrationService->dbVersion();

        return ExitCode::OK;
    }
}
