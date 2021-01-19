<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Informer;

final class MigrationInformerType
{
    public const BEGIN_CREATE_HISTORY_TABLE = 1;
    public const END_CREATE_HISTORY_TABLE = 2;
    public const BEGIN_COMMAND = 3;
    public const END_COMMAND = 4;
}
