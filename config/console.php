<?php

declare(strict_types=1);

use Yiisoft\Aliases\Aliases;

return [
    Aliases::class => [
        '@yiisoft/yii/db/migration' => dirname(__DIR__)
    ],
];
