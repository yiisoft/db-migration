<?php

declare(strict_types=1);

use Yiisoft\Yii\Db\Migration\Command\CreateCommand;
use Yiisoft\Yii\Db\Migration\Command\DownCommand;
use Yiisoft\Yii\Db\Migration\Command\HistoryCommand;
use Yiisoft\Yii\Db\Migration\Command\ListTablesCommand;
use Yiisoft\Yii\Db\Migration\Command\NewCommand;
use Yiisoft\Yii\Db\Migration\Command\RedoCommand;
use Yiisoft\Yii\Db\Migration\Command\UpdateCommand;

return [
    'yiisoft/aliases' => [
        'aliases' => [
            '@yiisoft/yii/db/migration' => '@vendor/yiisoft/yii-db-migration',
        ],
    ],

    'yiisoft/yii-console' => [
        'commands' => [
            'migrate/create' => CreateCommand::class,
            'database/list' => ListTablesCommand::class,
            'migrate/down' => DownCommand::class,
            'migrate/history' => HistoryCommand::class,
            'migrate/new' => NewCommand::class,
            'migrate/redo' => RedoCommand::class,
            'migrate/up' => UpdateCommand::class,
        ],
    ],

    'yiisoft/yii-db-migration' => [
        'viewsBasePath' => '@yiisoft/yii/db/migration/resources/view',
        'createNamespace' => '',
        'createPath' => '',
        'updateNamespaces' => [],
        'updatePaths' => [],
    ],
];
