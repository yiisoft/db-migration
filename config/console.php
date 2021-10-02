<?php

declare(strict_types=1);

use Yiisoft\Yii\Db\Migration\Informer\MigrationInformerInterface;
use Yiisoft\Yii\Db\Migration\Informer\NullMigrationInformer;
use Yiisoft\Yii\Db\Migration\Service\MigrationService;

/** @var array $params */

return [
    MigrationService::class => [
        'class' => MigrationService::class,
        'createNamespace()' => [$params['yiisoft/yii-db-migration']['createNamespace']],
        'updateNamespaces()' => [$params['yiisoft/yii-db-migration']['updateNamespaces']],
        'createPath()' => [$params['yiisoft/yii-db-migration']['createPath']],
        'updatePaths()' => [$params['yiisoft/yii-db-migration']['updatePaths']],
    ],

    MigrationInformerInterface::class => NullMigrationInformer::class,
];
