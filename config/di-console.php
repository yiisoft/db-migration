<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\DatabaseSetRegistry;
use Yiisoft\Db\Migration\Informer\ConsoleMigrationInformer;
use Yiisoft\Db\Migration\Informer\MigrationInformerInterface;
use Yiisoft\Db\Migration\Service\MigrationService;

/** @var array $params */

return [
    DatabaseSetRegistry::class => [
        'class' => DatabaseSetRegistry::class,
        '__construct()' => [
            'databases' => $params['yiisoft/db-migration']['databases'] ?? [],
        ],
    ],

    MigrationService::class => [
        'class' => MigrationService::class,
        'setNewMigrationNamespace()' => [$params['yiisoft/db-migration']['newMigrationNamespace']],
        'setSourceNamespaces()' => [$params['yiisoft/db-migration']['sourceNamespaces']],
        'setNewMigrationPath()' => [$params['yiisoft/db-migration']['newMigrationPath']],
        'setSourcePaths()' => [$params['yiisoft/db-migration']['sourcePaths']],
    ],

    MigrationInformerInterface::class => ConsoleMigrationInformer::class,
];
