<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration;

use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;
use Yiisoft\Yii\Db\Migration\Informer\MigrationInformerInterface;
use Yiisoft\Yii\Db\Migration\Informer\NullMigrationInformer;

final class Migrator
{
    private bool $checkMigrationHistoryTable = true;
    private bool $schemaCacheEnabled = false;

    public function __construct(
        private ConnectionInterface $db,
        private SchemaCache $schemaCache,
        private MigrationInformerInterface $informer,
        private string $historyTable = '{{%migration}}',
        private ?int $migrationNameLimit = 180
    ) {
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

        $tableSchema = $this->db->getSchema()->getTableSchema($this->historyTable);

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
        return $migration::class;
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
        $this->schemaCacheEnabled = $this->schemaCache->isEnabled();
        if ($this->schemaCacheEnabled) {
            $this->schemaCache->setEnabled(false);
        }
    }

    private function afterMigrate(): void
    {
        if ($this->schemaCacheEnabled) {
            $this->schemaCache->setEnabled(true);
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
