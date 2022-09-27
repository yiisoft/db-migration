<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Service\Generate;

final class Column
{
    /**
     * @param string[] $decorators
     */
    public function __construct(private string $property, private array $decorators = [])
    {
    }

    public function getProperty(): string
    {
        return $this->property;
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
