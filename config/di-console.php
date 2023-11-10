<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\Informer\ConsoleMigrationInformer;
use Yiisoft\Db\Migration\Informer\MigrationInformerInterface;
use Yiisoft\Db\Migration\Service\MigrationService;

/** @var array $params */

return [
    MigrationService::class => [
        'class' => MigrationService::class,
        'setCreateNamespace()' => [$params['yiisoft/db-migration']['createNamespace']],
        'setUpdateNamespaces()' => [$params['yiisoft/db-migration']['updateNamespaces']],
        'setCreatePath()' => [$params['yiisoft/db-migration']['createPath']],
        'setUpdatePaths()' => [$params['yiisoft/db-migration']['updatePaths']],
    ],

    MigrationInformerInterface::class => ConsoleMigrationInformer::class,
];
