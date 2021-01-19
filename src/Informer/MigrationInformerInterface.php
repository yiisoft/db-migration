<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Informer;

interface MigrationInformerInterface
{
    public function info(int $type, string $message): void;
}
