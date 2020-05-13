<?php

declare(strict_types=1);

use Yiisoft\Yii\Db\Migration\Command\CreateCommand;
use Yiisoft\Yii\Db\Migration\Command\ListTableCommand;
use Yiisoft\Yii\Db\Migration\Command\DownCommand;
use Yiisoft\Yii\Db\Migration\Command\HistoryCommand;
use Yiisoft\Yii\Db\Migration\Command\NewCommand;
use Yiisoft\Yii\Db\Migration\Command\RedoCommand;
use Yiisoft\Yii\Db\Migration\Command\ToCommand;
use Yiisoft\Yii\Db\Migration\Command\UpdateCommand;

return [
    'console' => [
        'id' => 'yii-migration',
        'name' => 'Yii Db Migration Tool Generator',
        'commands' => [
            'generate/create' => CreateCommand::class,
            'database/list' => ListTableCommand::class,
            'migrate/down' => DownCommand::class,
            'migrate/history' => HistoryCommand::class,
            'migrate/new' => NewCommand::class,
            'migrate/redo' => RedoCommand::class,
            'migrate/up' => UpdateCommand::class
        ],
    ]
];
