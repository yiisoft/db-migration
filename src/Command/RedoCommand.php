<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;
use Yiisoft\Db\Migration\Command\Context\RedoContext;
use Yiisoft\Db\Migration\DatabaseSet;
use Yiisoft\Db\Migration\DatabaseSetRegistry;
use Yiisoft\Db\Migration\Migrator;
use Yiisoft\Db\Migration\Runner\DownRunner;
use Yiisoft\Db\Migration\Runner\UpdateRunner;
use Yiisoft\Db\Migration\Service\MigrationService;

use function array_keys;
use function array_reverse;
use function count;
use function array_slice;

/**
 * Redoes the last few migrations.
 *
 * This command will first revert the specified migrations, and then apply them again.
 *
 * For example:
 *
 * ```shell
 * ./yii migrate:redo                                           # redo the last applied migration
 * ./yii migrate:redo --limit=3                                 # redo last 3 applied migrations
 * ./yii migrate:redo --all                                     # redo all migrations
 * ./yii migrate:redo --path=@vendor/yiisoft/rbac-db/migrations # redo the last migration from the directory
 * ./yii migrate:redo --namespace=Yiisoft\\Rbac\\Db\\Migrations # redo the last migration from the namespace
 * ```
 *
 * @extends DatabaseCommand<RedoContext>
 */
#[AsCommand('migrate:redo', 'Redoes the last few migrations.')]
final class RedoCommand extends DatabaseCommand
{
    public function __construct(
        private readonly MigrationService $migrationService,
        private readonly Migrator $migrator,
        private readonly DownRunner $downRunner,
        private readonly UpdateRunner $updateRunner,
        ?DatabaseSetRegistry $databases = null,
    ) {
        parent::__construct($databases);
    }

    protected function configure(): void
    {
        $this
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Number of migrations to redo.', 1)
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Redo all migrations.')
            ->addOption('path', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Path to migrations to redo.')
            ->addOption('namespace', 'ns', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Namespace of migrations to redo.')
            ->addOption('force-yes', 'y', InputOption::VALUE_NONE, 'Force yes to all questions.');
    }

    protected function getMissingDatabaseOptionPolicy(InputInterface $input): MissingDatabaseOptionPolicy
    {
        return MissingDatabaseOptionPolicy::RejectIfMultiple;
    }

    protected function createDefaultContext(): RedoContext
    {
        return new RedoContext($this->migrator, $this->migrationService, $this->downRunner, $this->updateRunner);
    }

    protected function createContext(
        DatabaseSet $database,
        Migrator $migrator,
        MigrationService $migrationService,
    ): RedoContext {
        return new RedoContext(
            $migrator,
            $migrationService,
            new DownRunner($migrator),
            new UpdateRunner($migrator),
        );
    }

    protected function executeForDatabase(
        InputInterface $input,
        OutputInterface $output,
        object $context,
    ): int {
        $io = new SymfonyStyle($input, $output);
        $context->migrator->setIo($io);
        $context->migrationService->setIo($io);
        $context->downRunner->setIo($io);
        $context->updateRunner->setIo($io);

        $context->migrationService->databaseConnection();

        $context->migrationService->before($this->getName() ?? '');

        $limit = !$input->getOption('all')
            ? (int) $input->getOption('limit')
            : null;

        if ($limit !== null && $limit <= 0) {
            $io->error('The limit option must be greater than 0.');

            return Command::INVALID;
        }

        /** @var string[] $paths */
        $paths = $input->getOption('path');
        /** @var string[] $namespaces */
        $namespaces = $input->getOption('namespace');

        if (!empty($paths) || !empty($namespaces)) {
            $migrations = $context->migrator->getHistory();
            $migrations = array_keys($migrations);
            $migrations = $context->migrationService->filterMigrations($migrations, $namespaces, $paths);

            if (empty($migrations)) {
                $io->warning('No applied migrations found.');

                return Command::FAILURE;
            }

            if ($limit !== null) {
                $migrations = array_slice($migrations, 0, $limit);
            }
        } else {
            $migrations = $context->migrator->getHistory($limit);

            if (empty($migrations)) {
                $io->warning('No migration has been done before.');

                return Command::FAILURE;
            }

            $migrations = array_keys($migrations);
        }

        $countMigrations = count($migrations);
        $migrationWord = $countMigrations === 1 ? 'migration' : 'migrations';

        $output->writeln("<warning>Total $countMigrations $migrationWord to be redone:</warning>\n");

        foreach ($migrations as $i => $migration) {
            $output->writeln("\t<info>" . ($i + 1) . ". $migration</info>");
        }

        if ($input->getOption('force-yes') || $io->confirm("Redo the above $migrationWord?")) {
            $instances = $context->migrationService->makeRevertibleMigrations($migrations);
            $migrationWas = ($countMigrations === 1 ? 'migration was' : 'migrations were');

            foreach ($instances as $i => $instance) {
                try {
                    $context->downRunner->run($instance, $i + 1);
                } catch (Throwable $e) {
                    $output->writeln("\n<fg=yellow>Total $i out of $countMigrations $migrationWas reverted.</>\n");
                    $io->error($i > 0 ? 'Partially reverted.' : 'Not reverted.');

                    throw $e;
                }
            }

            foreach (array_reverse($instances) as $i => $instance) {
                try {
                    $context->updateRunner->run($instance, $countMigrations - $i);
                } catch (Throwable $e) {
                    $output->writeln("\n<fg=yellow>Total $i out of $countMigrations $migrationWas applied.</>\n");
                    $io->error($i > 0 ? 'Reverted but partially applied.' : 'Reverted but not applied.');

                    throw $e;
                }
            }

            $output->writeln("\n<info>$countMigrations $migrationWas redone.</info>\n");
            $io->success('Migration redone successfully.');
        }

        return Command::SUCCESS;
    }
}
