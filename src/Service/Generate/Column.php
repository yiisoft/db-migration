<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Service\Generate;

final class Column
{
    /**
     * @param string[] $decorators
     */
    public function __construct(
        private readonly string $name,
        private readonly array $decorators = [],
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getDecorators(): array
    {
        return $this->decorators;
    }

    public function getDecoratorsString(): string
    {
        return implode('->', $this->decorators);
    }

    public function hasDecorators(): bool
    {
        return $this->decorators !== [];
    }
}
