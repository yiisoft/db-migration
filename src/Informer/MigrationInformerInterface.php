<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Informer;

/**
 * Handles migration process informational messages.
 */
interface MigrationInformerInterface
{
    public function beginCreateHistoryTable(string $message): void;

    public function endCreateHistoryTable(string $message): void;

    public function beginCommand(string $message): void;

    public function endCommand(string $message): void;
}
