<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Service;

use Yiisoft\Db\Connection\Connection;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Db\Migration\Helper\ConsoleHelper;

use function array_column;
use function array_merge;
use function preg_match;

final class DatabaseService
{
    private Connection $db;
    private ConsoleHelper $consoleHelper;
    private MigrationService $migrationService;

    public function __construct(Connection $db, ConsoleHelper $consoleHelper, MigrationService $migrationService)
    {
        $this->db = $db;
        $this->consoleHelper = $consoleHelper;
        $this->migrationService = $migrationService;
    }

    public function listTables(): int
    {
        $this->migrationService->title();
        $this->consoleHelper->io()->section('Command: migrate/list');

        $tables = $this->getAllTableNames();
        $migrationTable = $this->db->getSchema()->getRawTableName($this->migrationService->getMigrationTable());

        if (empty($tables) || implode(',', $tables) === $migrationTable) {
            $this->consoleHelper->io()->error('Your database does not contain any tables yet.');

            return ExitCode::DATAERR;
        }

        $dbname = $this->getDsnAttribute('dbname', $this->db->getDSN());

        $this->consoleHelper->io()->section("List of tables for database: {$dbname}");

        $count = 0;

        $this->consoleHelper->table()->setHeaders(['Nº', 'Table']);

        foreach ($tables as $key => $value) {
            if ($value !== $migrationTable) {
                $count++;
                $this->consoleHelper->table()->addRow([(string)($count), (string)($value)]);
            }
        }

        $this->consoleHelper->table()->render();
        $this->consoleHelper->output()->writeln("\n");
        $this->consoleHelper->io()->success("Lists all tables in the database.");
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

        if ($schemaNames === null || count($schemaNames) < 2) {
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

        return $result;
    }
}
