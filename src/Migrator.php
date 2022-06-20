<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration;

use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Db\Cache\QueryCache;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;
use Yiisoft\Yii\Db\Migration\Informer\MigrationInformerInterface;
use Yiisoft\Yii\Db\Migration\Informer\NullMigrationInformer;

use function get_class;

final class Migrator
{
    private ConnectionInterface $db;
    private SchemaCache $schemaCache;
    private QueryCache $queryCache;
    private MigrationInformerInterface $informer;

    private string $historyTable;
    private ?int $migrationNameLimit;

    private bool $checkMigrationHistoryTable = true;
    private bool $schemaCacheEnabled = false;
    private bool $queryCacheEnabled = false;

    public function __construct(
        ConnectionInterface $db,
        SchemaCache $schemaCache,
        QueryCache $queryCache,
        MigrationInformerInterface $informer,
        string $historyTable = '{{%migration}}',
        ?int $maxMigrationNameLength = 180
    ) {
        $this->db = $db;
        $this->schemaCache = $schemaCache;
        $this->queryCache = $queryCache;
        $this->informer = $informer;

        $this->historyTable = $historyTable;
        $this->migrationNameLimit = $maxMigrationNameLength;
    }

    public function setInformer(MigrationInformerInterface $informer): void
    {
        $this->informer = $informer;
    }

    public function setIO(?SymfonyStyle $io): void
    {
        $this->informer->setIO($io);
    }

    public function up(MigrationInterface $migration): void
    {
        $this->checkMigrationHistoryTable();

        $this->beforeMigrate();
        $migration->up($this->createBuilder());
        $this->afterMigrate();

        $this->addMigrationToHistory($migration);
    }

    public function down(RevertibleMigrationInterface $migration): void
    {
        $this->checkMigrationHistoryTable();

        $this->beforeMigrate();
        $migration->down($this->createBuilder());
        $this->afterMigrate();

        $this->removeMigrationFromHistory($migration);
    }

    public function getMigrationNameLimit(): ?int
    {
        if ($this->migrationNameLimit !== null) {
            return $this->migrationNameLimit;
        }

        $tableSchema = $this->db->getSchema()->getTableSchema($this->historyTable, true);
        if ($tableSchema === null) {
            return null;
        }

        $limit = $tableSchema->getColumns()['name']->getSize();
        if ($limit === null) {
            return null;
        }

        return $this->migrationNameLimit = $limit;
    }

    public function getHistory(?int $limit = null): array
    {
        $this->checkMigrationHistoryTable();

        $query = (new Query($this->db))
            ->select(['name', 'apply_time'])
            ->from($this->historyTable)
            ->orderBy(['apply_time' => SORT_DESC, 'id' => SORT_DESC]);

        if ($limit > 0) {
            $query->limit($limit);
        }

        /** @psalm-var array<int,array<string,string|null>> $rows */
        $rows = $query->all();

        return ArrayHelper::map($rows, 'name', 'apply_time');
    }

    public function getHistoryTable(): string
    {
        return $this->historyTable;
    }

    private function addMigrationToHistory(MigrationInterface $migration): void
    {
        $this->db->createCommand()->insert(
            $this->historyTable,
            [
                'name' => $this->getMigrationName($migration),
                'apply_time' => time(),
            ]
        )->execute();
    }

    private function removeMigrationFromHistory(MigrationInterface $migration): void
    {
        $command = $this->db->createCommand();
        $command->delete($this->historyTable, [
            'name' => $this->getMigrationName($migration),
        ])->execute();
    }

    private function getMigrationName(MigrationInterface $migration): string
    {
        return get_class($migration);
    }

    private function checkMigrationHistoryTable(): void
    {
        if (!$this->checkMigrationHistoryTable) {
            return;
        }

        if ($this->db->getSchema()->getTableSchema($this->historyTable, true) === null) {
            $this->createMigrationHistoryTable();
        }

        $this->checkMigrationHistoryTable = false;
    }

    private function createMigrationHistoryTable(): void
    {
        $tableName = $this->db->getSchema()->getRawTableName($this->historyTable);
        $this->informer->beginCreateHistoryTable('Creating migration history table "' . $tableName . '"...');

        $this->beforeMigrate();

        $b = $this->createBuilder(new NullMigrationInformer());
        $b->createTable($this->historyTable, [
            'id' => $b->primaryKey(),
            'name' => $b->string($this->migrationNameLimit)->notNull(),
            'apply_time' => $b->integer()->notNull(),
        ]);

        $this->afterMigrate();

        $this->informer->endCreateHistoryTable('Done.');
    }

    private function beforeMigrate(): void
    {
        $this->queryCacheEnabled = $this->queryCache->isEnabled();
        if ($this->queryCacheEnabled) {
            $this->queryCache->setEnable(false);
        }

        $this->schemaCacheEnabled = $this->schemaCache->isEnabled();
        if ($this->schemaCacheEnabled) {
            $this->schemaCache->setEnable(false);
        }
    }

    private function afterMigrate(): void
    {
        if ($this->queryCacheEnabled) {
            $this->queryCache->setEnable(true);
        }

        if ($this->schemaCacheEnabled) {
            $this->schemaCache->setEnable(true);
        }

        $this->db->getSchema()->refresh();
    }

    private function createBuilder(?MigrationInformerInterface $informer = null): MigrationBuilder
    {
        return new MigrationBuilder(
            $this->db,
            $informer ?? $this->informer,
        );
    }
}
