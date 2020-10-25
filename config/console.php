<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Factory\Definitions\Reference;
use Yiisoft\Yii\Db\Migration\Helper\ConsoleHelper;
use Yiisoft\Yii\Db\Migration\Service\MigrationService;

/** @var array $params */

return [
    MigrationService::class => [
        '__class' => MigrationService::class,
        '__construct()' => [
            Reference::to(ConnectionInterface::class),
            Reference::to(ConsoleHelper::class)
        ],
        'createNamespace()' => [fn () => $params['yiisoft/yii-db-migration']['createNameSpace']],
        'updateNamespace()' => [
            fn () => $params['yiisoft/yii-db-migration']['updateNameSpace']
        ],
        'createPath()' => [$params['yiisoft/yii-db-migration']['createPath']],
        'updatePath()' => [$params['yiisoft/yii-db-migration']['updatePath']]
    ]
];
