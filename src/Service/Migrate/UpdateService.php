<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Service\Migrate;

use function microtime;
use function sprintf;

use Yiisoft\Yii\Db\Migration\Helper\ConsoleHelper;
use Yiisoft\Yii\Db\Migration\Service\MigrationService;

final class UpdateService
{
    private ConsoleHelper $consoleHelper;
    private MigrationService $migrationService;

    public function __construct(ConsoleHelper $consoleHelper, MigrationService $migrationService)
    {
        $this->consoleHelper = $consoleHelper;
        $this->migrationService = $migrationService;
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
        if ($class === $this->migrationService::BASE_MIGRATION) {
            return true;
        }

        $this->consoleHelper->io()->title("\nApplying $class:");
        $start = microtime(true);

        $migration = $this->migrationService->createMigration($class);

        if ($migration === null) {
            $time = microtime(true) - $start;
            $this->consoleHelper->io()->error("Failed to revert $class. Unable to get migration instance (time: " . sprintf('%.3f', $time) . 's)');
            return false;
        }

        $migration->up();
        $this->migrationService->addMigrationHistory($class);
        $time = microtime(true) - $start;
        $this->consoleHelper->output()->writeln(
            "\n\t<info>>>> [OK] - Applied $class (time: " . sprintf('%.3f', $time) . 's)<info>'
        );

        return true;
    }
}
