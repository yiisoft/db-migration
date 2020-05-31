<?php

declare(strict_types=1);

use Yiisoft\Yii\Db\Migration\Command\CreateCommand;
use Yiisoft\Yii\Db\Migration\Command\ListTablesCommand;
use Yiisoft\Yii\Db\Migration\Command\DownCommand;
use Yiisoft\Yii\Db\Migration\Command\HistoryCommand;
use Yiisoft\Yii\Db\Migration\Command\NewCommand;
use Yiisoft\Yii\Db\Migration\Command\RedoCommand;
use Yiisoft\Yii\Db\Migration\Command\UpdateCommand;

return [
    'console' => [
        'id' => 'yii-db-migration',
        'name' => 'Yii Database Migration Tool',
        'commands' => [
            'generate/create' => CreateCommand::class,
            'database/list' => ListTablesCommand::class,
            'migrate/down' => DownCommand::class,
            'migrate/history' => HistoryCommand::class,
            'migrate/new' => NewCommand::class,
            'migrate/redo' => RedoCommand::class,
            'migrate/up' => UpdateCommand::class
        ],
    ]
];
