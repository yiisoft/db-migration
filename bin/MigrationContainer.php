<?php

declare(strict_types=1);

use Symfony\Component\Console\Application;
use Yiisoft\Db\Migration\Informer\ConsoleMigrationInformer;
use Yiisoft\Db\Migration\Migrator;
use Yiisoft\Definitions\ReferencesArray;
use Yiisoft\Db\Migration\Informer\MigrationInformerInterface;
use Yiisoft\Db\Migration\Service\MigrationService;
use Yiisoft\Db\Migration\Command\CreateCommand;
use Yiisoft\Db\Migration\Command\DownCommand;
use Yiisoft\Db\Migration\Command\HistoryCommand;
use Yiisoft\Db\Migration\Command\NewCommand;
use Yiisoft\Db\Migration\Command\RedoCommand;
use Yiisoft\Db\Migration\Command\UpdateCommand;

final class MigrationContainer
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
                'setCreateNamespace()' => [''],
                'setCreatePath()' => [''],
                'setUpdateNamespaces()' => [[]],
                'setUpdatePaths()' => [[]],
            ],
            Migrator::class => [
                '__constructor()' => [
                    'historyTable' => '{{%migration}}',
                    'migrationNameLimit' => 180,
                    'maxSqlOutputLength' => null,
                ],
            ],
            MigrationInformerInterface::class => ConsoleMigrationInformer::class,
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
