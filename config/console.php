<?php

declare(strict_types=1);

use Yiisoft\Yii\Db\Migration\Service\MigrationService;
use Yiisoft\View\View;

/** @var array $params */

return [
    MigrationService::class => [
        '__class' => MigrationService::class,
        'createNamespace()' => [fn () => $params['yiisoft/yii-db-migration']['createNamespace']],
        'updateNamespace()' => [
            fn () => $params['yiisoft/yii-db-migration']['updateNamespace'],
        ],
        'createPath()' => [$params['yiisoft/yii-db-migration']['createPath']],
        'updatePath()' => [$params['yiisoft/yii-db-migration']['updatePath']],
    ],
];
