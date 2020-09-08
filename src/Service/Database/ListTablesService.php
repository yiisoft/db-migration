<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Service\Database;

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Db\Migration\Helper\ConsoleHelper;
use Yiisoft\Yii\Db\Migration\Service\MigrationService;

use function array_column;
use function array_merge;
use function implode;
use function preg_match;

final class ListTablesService
{
    private ConnectionInterface $db;
    private ConsoleHelper $consoleHelper;
    private MigrationService $migrationService;

    public function __construct(
        ConnectionInterface $db,
        ConsoleHelper $consoleHelper,
        MigrationService $migrationService
    ) {
        $this->db = $db;
        $this->consoleHelper = $consoleHelper;
        $this->migrationService = $migrationService;
    }

    public function run(): int
    {
        $tables = $this->getAllTableNames();
        $migrationTable = $this->db->getSchema()->getRawTableName($this->migrationService->getMigrationTable());
        $dsn = $this->db->getDSN();

        if ($dsn === null) {
            $this->consoleHelper->io()->error('Dsn cannot be empty.');

            return ExitCode::UNSPECIFIED_ERROR;
        }

        if (empty($tables) || implode(',', $tables) === $migrationTable) {
            $this->consoleHelper->io()->error('Your database does not contain any tables yet.');

            return ExitCode::UNSPECIFIED_ERROR;
        }

        $dbname = $this->getDsnAttribute('dbname', $dsn);

        $this->consoleHelper->io()->section("List of tables for database: {$dbname}");

        $count = 0;

        $this->consoleHelper->table()->setHeaders(['Nº', 'Table']);

        foreach ($tables as $value) {
            if ($value !== $migrationTable) {
                $count++;
                $this->consoleHelper->table()->addRow([(string)($count), (string)($value)]);
            }
        }

        $this->consoleHelper->table()->render();
        $this->migrationService->dbVersion();

        return ExitCode::OK;
    }

    private function getAllTableNames(): array
    {
        $tables = [];
        $schemaNames = [];

        try {
            $schemaNames = $this->db->getSchema()->getSchemaNames(true);
        } catch (NotSupportedException $ex) {
        }

        if (count($schemaNames) < 2) {
            $tables = $this->db->getSchema()->getTableNames();
        } else {
            $schemaTables = [];
            foreach ($schemaNames as $schemaName) {
                $schemaTables[] = array_column($this->db->getSchema()->getTableSchemas($schemaName), 'fullName');
            }

            $tables = array_merge($tables, $schemaTables);
        }

        return $tables;
    }

    private function getDsnAttribute(string $name, string $dsn): ?string
    {
        $result = null;

        if (preg_match('/' . $name . '=([^;]*)/', $dsn, $match)) {
            $result = $match[1];
        }

        if (preg_match('~([^/]+)\.sq3~', $dsn, $match)) {
            $result = $match[1];
        }

        return $result;
    }
}
