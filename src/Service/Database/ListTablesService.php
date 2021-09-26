<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Service\Database;

use RuntimeException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Db\Migration\Migrator;
use Yiisoft\Yii\Db\Migration\Service\MigrationService;

use function array_column;
use function array_merge;
use function count;
use function implode;
use function preg_match;

final class ListTablesService
{
    private ConnectionInterface $db;
    private MigrationService $migrationService;
    private Migrator $migrator;
    private ?SymfonyStyle $io = null;

    public function __construct(
        ConnectionInterface $db,
        MigrationService $migrationService,
        Migrator $migrator
    ) {
        $this->db = $db;
        $this->migrationService = $migrationService;
        $this->migrator = $migrator;
    }

    public function withIO(?SymfonyStyle $io): self
    {
        $new = clone $this;
        $new->io = $io;
        $new->migrationService = $this->migrationService->withIO($io);
        return $new;
    }

    public function run(): int
    {
        if ($this->io === null) {
            throw new RuntimeException('Need set output decorator via `withIO()`.');
        }

        $tables = $this->getAllTableNames();
        $migrationTable = $this->db->getSchema()->getRawTableName($this->migrator->getHistoryTable());
        $dsn = $this->db->getDSN();

        if (empty($tables) || implode(',', $tables) === $migrationTable) {
            $this->io->error('Your database does not contain any tables yet.');

            return ExitCode::UNSPECIFIED_ERROR;
        }

        $dbname = $this->getDsnAttribute('dbname', $dsn);

        $this->io->section("List of tables for database: {$dbname}");

        $count = 0;

        $table = new Table($this->io);
        $table->setHeaders(['Nº', 'Table']);

        foreach ($tables as $value) {
            if ($value !== $migrationTable) {
                $count++;
                $table->addRow([(string) ($count), (string) ($value)]);
            }
        }

        $table->render();
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
