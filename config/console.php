<?php

declare(strict_types=1);

use Yiisoft\Aliases\Aliases;
use Yiisoft\Factory\Definition\Reference;
use Yiisoft\View\View;
use Yiisoft\Yii\Db\Migration\Informer\MigrationInformerInterface;
use Yiisoft\Yii\Db\Migration\Informer\NullMigrationInformer;
use Yiisoft\Yii\Db\Migration\Service\Generate\CreateService;
use Yiisoft\Yii\Db\Migration\Service\MigrationService;

/** @var array $params */

return [
    MigrationService::class => [
        'class' => MigrationService::class,
        'createNamespace()' => [$params['yiisoft/yii-db-migration']['createNamespace']],
        'updateNamespaces()' => [$params['yiisoft/yii-db-migration']['updateNamespaces']],
        'createPath()' => [$params['yiisoft/yii-db-migration']['createPath']],
        'updatePath()' => [$params['yiisoft/yii-db-migration']['updatePath']],
    ],

    MigrationInformerInterface::class => NullMigrationInformer::class,

    'yii-db-migration-view' => [
        'class' => View::class,
        '__construct()' => [
            'basePath' => static fn (Aliases $aliases) => $aliases->get(
                $params['yiisoft/yii-db-migration']['viewsBasePath'],
            ),
        ],
    ],

    CreateService::class => [
        'class' => CreateService::class,
        '__construct()' => [
            'view' => Reference::to('yii-db-migration-view'),
        ],
    ],
];
