<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Service\Generate;

final class ForeignKey
{
    private string $indexName;
    private string $foreignKeyName;
    private ?string $column;
    private string $relatedTable;
    private string $relatedColumn;

    public function __construct(
        string $indexName,
        string $foreignKeyName,
        ?string $column,
        string $relatedTable,
        string $relatedColumn
    ) {
        $this->indexName = $indexName;
        $this->foreignKeyName = $foreignKeyName;
        $this->column = $column;
        $this->relatedTable = $relatedTable;
        $this->relatedColumn = $relatedColumn;
    }

    public function getIndexName(): string
    {
        return $this->indexName;
    }

    public function getForeignKeyName(): string
    {
        return $this->foreignKeyName;
    }

    public function getColumn(): ?string
    {
        return $this->column;
    }

    public function getRelatedTable(): string
    {
        return $this->relatedTable;
    }

    public function getRelatedColumn(): string
    {
        return $this->relatedColumn;
    }
}
