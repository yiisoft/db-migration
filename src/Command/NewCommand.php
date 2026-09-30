<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Db\Migration\DatabaseContext;
use Yiisoft\Db\Migration\DatabaseSetRegistry;
use Yiisoft\Db\Migration\Migrator;
use Yiisoft\Db\Migration\Service\MigrationService;

use function array_slice;
use function count;

/**
 * Displays not yet applied migrations.
 *
 * This command will show the new migrations that have not been applied yet.
 *
 * For example:
 *
 * ```shell
 * ./yii migrate:new                                           # first 10 new migrations
 * ./yii migrate:new --limit=5                                 # first 5 new migrations
 * ./yii migrate:new --all                                     # all new migrations
 * ./yii migrate:new --path=@vendor/yiisoft/rbac-db/migrations # new migrations from the directory
 * ./yii migrate:new --namespace=Yiisoft\\Rbac\\Db\\Migrations # new migrations from the namespace
 *
 * # new migrations from multiple directories and namespaces
 * ./yii migrate:new --path=@vendor/yiisoft/rbac-db/migrations --path=@vendor/yiisoft/cache-db/migrations
 * ./yii migrate:new --namespace=Yiisoft\\Rbac\\Db\\Migrations --namespace=Yiisoft\\Cache\\Db\\Migrations
 * ```
 */
#[AsCommand('migrate:new', 'Displays not yet applied migrations.')]
final class NewCommand extends DatabaseCommand
{
    public function __construct(
        private readonly MigrationService $migrationService,
        private readonly Migrator $migrator,
        ?DatabaseSetRegistry $databases = null,
    ) {
        parent::__construct($databases);
    }

    protected function configure(): void
    {
        $this
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Number of migrations to display.', 10)
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'All new migrations.')
            ->addOption('path', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Path to migrations to display.')
            ->addOption('namespace', 'ns', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Namespace of migrations to display.');
    }

    protected function executeForDatabase(
        InputInterface $input,
        OutputInterface $output,
        ?DatabaseContext $context,
    ): int {
        $migrator = $context?->migrator ?? $this->migrator;
        $migrationService = $context?->migrationService ?? $this->migrationService;

        $io = new SymfonyStyle($input, $output);
        $migrator->setIo($io);
        $migrationService->setIo($io);

        $migrationService->databaseConnection();

        /** @var string[] $paths */
        $paths = $input->getOption('path');

        /** @var string[] $namespaces */
        $namespaces = $input->getOption('namespace');

        if (!empty($paths) || !empty($namespaces)) {
            $migrationService->setSourcePaths($paths);
            $migrationService->setSourceNamespaces($namespaces);
        }

        $migrationService->before($this->getName() ?? '');

        $limit = !$input->getOption('all')
            ? (int) $input->getOption('limit')
            : null;

        if ($limit !== null && $limit <= 0) {
            $io->error('The limit option must be greater than 0.');

            return Command::INVALID;
        }

        $migrations = $migrationService->getNewMigrations();

        if (empty($migrations)) {
            $io->warning('No new migrations found. Your system is up-to-date.');

            return Command::FAILURE;
        }

        $countMigrations = count($migrations);
        $migrationWord = $countMigrations === 1 ? 'migration' : 'migrations';

        if ($limit !== null && $countMigrations > $limit) {
            $migrations = array_slice($migrations, 0, $limit);

            $io->warning("Showing $limit out of $countMigrations new $migrationWord:\n");
        } else {
            $io->section("Found $countMigrations new $migrationWord:");
        }

        foreach ($migrations as $i => $migration) {
            $output->writeln("<info>\t" . ($i + 1) . ". $migration</info>");
        }

        return Command::SUCCESS;
    }
}
