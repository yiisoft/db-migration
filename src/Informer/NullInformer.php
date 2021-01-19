<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Informer;

final class NullInformer implements InformerInterface
{
    public function info(int $type, string $message): void
    {
        // noop
    }
}
