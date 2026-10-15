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
use Yiisoft\Db\Migration\Command\Context\UpdateContext;
use Yiisoft\Db\Migration\DatabaseSet;
use Yiisoft\Db\Migration\DatabaseSetRegistry;
use Yiisoft\Db\Migration\Migrator;
use Yiisoft\Db\Migration\Runner\UpdateRunner;
use Yiisoft\Db\Migration\Service\MigrationService;

use function array_slice;
use function count;
use function strlen;

/**
 * Applies new migrations.
 *
 * For example,
 *
 * ```shell
 * ./yii migrate:up                                           # apply all new migrations
 * ./yii migrate:up --limit=3                                 # apply the first 3 new migrations
 * ./yii migrate:up --path=@vendor/yiisoft/rbac-db/migrations # apply new migrations from the directory
 * ./yii migrate:up --namespace=Yiisoft\\Rbac\\Db\\Migrations # apply new migrations from the namespace
 *
 * # apply new migrations from multiple directories and namespaces
 * ./yii migrate:up --path=@vendor/yiisoft/rbac-db/migrations --path=@vendor/yiisoft/cache-db/migrations
 * ./yii migrate:up --namespace=Yiisoft\\Rbac\\Db\\Migrations --namespace=Yiisoft\\Cache\\Db\\Migrations
 * ```
 *
 * @extends DatabaseCommand<UpdateContext>
 */
#[AsCommand('migrate:up', 'Applies new migrations.')]
final class UpdateCommand extends DatabaseCommand
{
    public function __construct(
        private readonly UpdateRunner $updateRunner,
        private readonly MigrationService $migrationService,
        private readonly Migrator $migrator,
        ?DatabaseSetRegistry $databases = null,
    ) {
        parent::__construct($databases);
    }

    protected function configure(): void
    {
        $this
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Number of migrations to apply.')
            ->addOption('path', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Path to migrations to apply.')
            ->addOption('namespace', 'ns', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Namespace of migrations to apply.')
            ->addOption('force-yes', 'y', InputOption::VALUE_NONE, 'Force yes to all questions.');
    }

    protected function getMissingDatabaseOptionPolicy(InputInterface $input): MissingDatabaseOptionPolicy
    {
        return $input->getOption('path') !== [] || $input->getOption('namespace') !== []
            ? MissingDatabaseOptionPolicy::RejectIfMultipleWithFilter
            : MissingDatabaseOptionPolicy::ProcessAllDatabases;
    }

    protected function createDefaultContext(): UpdateContext
    {
        return new UpdateContext($this->migrator, $this->migrationService, $this->updateRunner);
    }

    protected function createContext(
        DatabaseSet $database,
        Migrator $migrator,
        MigrationService $migrationService,
    ): UpdateContext {
        return new UpdateContext(
            $migrator,
            $migrationService,
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
        $context->updateRunner->setIo($io);

        $context->migrationService->databaseConnection();

        /** @var string[] $paths */
        $paths = $input->getOption('path');

        /** @var string[] $namespaces */
        $namespaces = $input->getOption('namespace');

        if (!empty($paths) || !empty($namespaces)) {
            $context->migrationService->setSourcePaths($paths);
            $context->migrationService->setSourceNamespaces($namespaces);
        }

        if ($context->migrationService->before($this->getName() ?? '') === Command::INVALID) {
            return Command::INVALID;
        }

        $limit = $input->getOption('limit');

        if ($limit !== null) {
            $limit = (int) $limit;

            if ($limit <= 0) {
                $io->error('The limit option must be greater than 0.');

                return Command::INVALID;
            }
        }

        $migrations = $context->migrationService->getNewMigrations();

        if (empty($migrations)) {
            $output->writeln("<fg=green>No new migrations found.</>\n");
            $io->success('Your system is up-to-date.');

            return Command::SUCCESS;
        }

        $migrationsCount = count($migrations);
        $migrationWord = $migrationsCount === 1 ? 'migration' : 'migrations';

        if ($limit !== null && $migrationsCount > $limit) {
            $migrations = array_slice($migrations, 0, $limit);

            $output->writeln("<fg=yellow>Total $limit out of $migrationsCount new $migrationWord to be applied:</>\n");
        } else {
            $output->writeln("<fg=yellow>Total $migrationsCount new $migrationWord to be applied:</>\n");
        }

        foreach ($migrations as $i => $migration) {
            $nameLimit = $context->migrator->getMigrationNameLimit();

            if (strlen($migration) > $nameLimit) {
                $output->writeln(
                    "\n<fg=red>The migration name '$migration' is too long. Its not possible to apply "
                    . 'this migration.</>',
                );

                return Command::INVALID;
            }

            $output->writeln("\t<fg=yellow>" . ($i + 1) . ". $migration</>");
        }

        if ($input->getOption('force-yes') || $io->confirm("Apply the above $migrationWord?")) {
            $instances = $context->migrationService->makeMigrations($migrations);
            $migrationWas = ($migrationsCount === 1 ? 'migration was' : 'migrations were');

            foreach ($instances as $i => $instance) {
                try {
                    $context->updateRunner->run($instance, $i + 1);
                } catch (Throwable $e) {
                    $output->writeln("\n<fg=yellow>Total $i out of $migrationsCount new $migrationWas applied.</>\n");
                    $io->error($i > 0 ? 'Partially updated.' : 'Not updated.');

                    throw $e;
                }
            }

            $output->writeln("\n<fg=green>Total $migrationsCount new $migrationWas applied.</>\n");
            $io->success('Updated successfully.');
        }

        return Command::SUCCESS;
    }
}
