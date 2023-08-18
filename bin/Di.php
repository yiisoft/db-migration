<?php

declare(strict_types=1);

use Yiisoft\Yii\Db\Migration\Informer\MigrationInformerInterface;
use Yiisoft\Yii\Db\Migration\Informer\NullMigrationInformer;
use Yiisoft\Yii\Db\Migration\Service\MigrationService;
use Yiisoft\Yii\Db\Migration\Command\CreateCommand;
use Yiisoft\Yii\Db\Migration\Command\DownCommand;
use Yiisoft\Yii\Db\Migration\Command\HistoryCommand;
use Yiisoft\Yii\Db\Migration\Command\NewCommand;
use Yiisoft\Yii\Db\Migration\Command\RedoCommand;
use Yiisoft\Yii\Db\Migration\Command\UpdateCommand;

final class Di
{
    public static function definitions(): array
    {
        return [
            MigrationService::class => [
                'class' => MigrationService::class,
                'createNamespace()' => [''],
                'createPath()' => [''],
                'updateNamespaces()' => [[]],
                'updatePaths()' => [[]],
            ],
            MigrationInformerInterface::class => NullMigrationInformer::class,
        ];
    }

    public static function getCommands(): array
    {
        return [
            CreateCommand::class,
            DownCommand::class,
            HistoryCommand::class,
            NewCommand::class,
            RedoCommand::class,
            UpdateCommand::class,
        ];
    }
}
