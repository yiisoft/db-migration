<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Service\Generate;

final class Column
{
    private string $property;

    /**
     * @var string[]
     */
    private array $decorators;

    /**
     * @param string[] $decorators
     */
    public function __construct(string $property, array $decorators = [])
    {
        $this->property = $property;
        $this->decorators = $decorators;
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
