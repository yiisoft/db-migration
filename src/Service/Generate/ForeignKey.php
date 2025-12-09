<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Service\Generate;

final class ForeignKey
{
    public function __construct(
        private readonly string $indexName,
        private readonly string $foreignKeyName,
        private readonly ?string $column,
        private readonly string $relatedTable,
        private readonly string $relatedColumn,
    ) {}

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
