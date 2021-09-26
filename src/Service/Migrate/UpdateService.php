<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Service\Migrate;

use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Yii\Db\Migration\Migrator;
use Yiisoft\Yii\Db\Migration\Service\MigrationService;

use function microtime;
use function sprintf;

final class UpdateService
{
    private MigrationService $migrationService;
    private Migrator $migrator;
    private ?SymfonyStyle $io = null;

    public function __construct(
        MigrationService $migrationService,
        Migrator $migrator
    ) {
        $this->migrationService = $migrationService;
        $this->migrator = $migrator;
    }

    public function withIO(?SymfonyStyle $io): self
    {
        $new = clone $this;
        $new->io = $io;
        $new->migrationService = $this->migrationService->withIO($io);
        $new->migrator->setIO($io);
        return $new;
    }

    /**
     * Upgrades with the specified migration class.
     *
     * @param string $class the migration class name
     *
     * @return bool whether the migration is successful
     */
    public function run(string $class): bool
    {
        if ($this->io) {
            $this->io->title("\nApplying $class:");
        }
        $start = microtime(true);

        $migration = $this->migrationService->createMigration($class);

        if ($migration === null) {
            $time = microtime(true) - $start;
            if ($this->io) {
                $this->io->error("Failed to revert $class. Unable to get migration instance (time: " . sprintf('%.3f', $time) . 's)');
            }
            return false;
        }

        $this->migrator->up($migration);

        $time = microtime(true) - $start;
        if ($this->io) {
            $this->io->writeln(
                "\n\t<info>>>> [OK] - Applied $class (time: " . sprintf('%.3f', $time) . 's)<info>'
            );
        }

        return true;
    }
}
