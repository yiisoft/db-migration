<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Service\Migrate;

use Yiisoft\Yii\Db\Migration\Helper\ConsoleHelper;
use Yiisoft\Yii\Db\Migration\Service\MigrationService;

use function microtime;
use function sprintf;

final class DownService
{
    private ConsoleHelper $consoleHelper;
    private MigrationService $migrationService;

    public function __construct(ConsoleHelper $consoleHelper, MigrationService $migrationService)
    {
        $this->consoleHelper = $consoleHelper;
        $this->migrationService = $migrationService;
    }

    /**
     * Downgrades with the specified migration class.
     *
     * @param string $class the migration class name
     *
     * @return bool whether the migration is successful
     */
    public function run(string $class): bool
    {
        if ($class === $this->migrationService::BASE_MIGRATION) {
            return true;
        }

        $this->consoleHelper->io()->title("\nreverting $class");

        $start = microtime(true);
        $migration = $this->migrationService->createMigration($class);

        if ($migration->safeDown() !== false) {
            $this->migrationService->removeMigrationHistory($class);
            $time = microtime(true) - $start;
            $this->consoleHelper->output()->writeln(
                "\n\t<info>>>> [Ok] -  reverted $class (time: " . sprintf('%.3f', $time) . "s)</info>"
            );

            return true;
        }

        $time = microtime(true) - $start;

        $this->consoleHelper->output()->writeln("\n");
        $this->consoleHelper->io()->error("Failed to revert $class (time: " . sprintf('%.3f', $time) . "s)");

        return false;
    }
}
