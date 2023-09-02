<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\Informer\MigrationInformerInterface;
use Yiisoft\Db\Migration\Informer\NullMigrationInformer;
use Yiisoft\Db\Migration\Service\MigrationService;

/** @var array $params */

return [
    MigrationService::class => [
        'class' => MigrationService::class,
        'createNamespace()' => [$params['yiisoft/db-migration']['createNamespace']],
        'updateNamespaces()' => [$params['yiisoft/db-migration']['updateNamespaces']],
        'createPath()' => [$params['yiisoft/db-migration']['createPath']],
        'updatePaths()' => [$params['yiisoft/db-migration']['updatePaths']],
    ],

    MigrationInformerInterface::class => NullMigrationInformer::class,
];
