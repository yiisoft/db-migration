<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Service\Migrate;

use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Yii\Db\Migration\Migrator;
use Yiisoft\Yii\Db\Migration\RevertibleMigrationInterface;
use Yiisoft\Yii\Db\Migration\Service\MigrationService;

use function microtime;
use function sprintf;

final class DownService
{
    private MigrationService $migrationService;
    private Migrator $migrator;

    public function __construct(
        MigrationService $migrationService,
        Migrator $migrator
    ) {
        $this->migrationService = $migrationService;
        $this->migrator = $migrator;
    }

    /**
     * Downgrades with the specified migration class.
     *
     * @param string $class the migration class name
     *
     * @return bool whether the migration is successful
     */
    public function run(string $class, ?SymfonyStyle $io = null): bool
    {
        if ($io) {
            $io->title("\nReverting $class");
        }

        $start = microtime(true);
        $migration = $this->migrationService->createMigration($class);

        if ($migration === null) {
            $time = microtime(true) - $start;
            if ($io) {
                $io->error("Failed to revert $class. Unable to get migration instance (time: " . sprintf('%.3f', $time) . 's)');
            }
            return false;
        }

        if (!$migration instanceof RevertibleMigrationInterface) {
            $time = microtime(true) - $start;
            if ($io) {
                $io->error("Failed to revert $class. Migration does not implement RevertibleMigrationInterface (time: " . sprintf('%.3f', $time) . 's)');
            }
            return false;
        }

        $this->migrator->down($migration);

        $time = microtime(true) - $start;
        if ($io) {
            $io->writeln(
                "\n\t<info>>>> [OK] -  Reverted $class (time: " . sprintf('%.3f', $time) . 's)</info>'
            );
        }

        return true;
    }
}
