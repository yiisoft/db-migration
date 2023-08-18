<?php

declare(strict_types=1);

use Symfony\Component\Console\Application;
use Yiisoft\Definitions\ReferencesArray;
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
            Application::class => [
                '__construct()' => [
                    'name' => 'Yii Database Migration Tool',
                    'version' => '1.0.0',
                ],
                'addCommands()' => [self::getCommands()],
            ],
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
        return ReferencesArray::from(
            [
                CreateCommand::class,
                DownCommand::class,
                HistoryCommand::class,
                NewCommand::class,
                RedoCommand::class,
                UpdateCommand::class,
            ]
        );
    }
}
