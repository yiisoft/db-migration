<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\DatabaseSetRegistry;
use Yiisoft\Db\Migration\Command\CommandFactory;
use Yiisoft\Db\Migration\Command\DatabaseCommand;
use Yiisoft\Db\Migration\Informer\ConsoleMigrationInformer;
use Yiisoft\Db\Migration\Informer\MigrationInformerInterface;
use Yiisoft\Db\Migration\Service\MigrationService;

/** @var array $params */

$definitions = [
    DatabaseSetRegistry::class => [
        'class' => DatabaseSetRegistry::class,
        '__construct()' => [
            'databases' => $params['yiisoft/db-migration']['databases'] ?? [],
        ],
    ],

    MigrationService::class => [
        'class' => MigrationService::class,
        'setNewMigrationNamespace()' => [($params['yiisoft/db-migration']['newMigrationNamespace'] ?? '')],
        'setSourceNamespaces()' => [($params['yiisoft/db-migration']['sourceNamespaces'] ?? [])],
        'setNewMigrationPath()' => [($params['yiisoft/db-migration']['newMigrationPath'] ?? '')],
        'setSourcePaths()' => [($params['yiisoft/db-migration']['sourcePaths'] ?? [])],
    ],

    MigrationInformerInterface::class => ConsoleMigrationInformer::class,
];

if (isset($params['yiisoft/db-migration']['databases']['default'])) {
    foreach (['newMigrationNamespace', 'newMigrationPath', 'sourceNamespaces', 'sourcePaths'] as $option) {
        if (\array_key_exists($option, $params['yiisoft/db-migration'])) {
            throw new LogicException('Configure default migrations either in databases["default"] or with migration parameters, not both. Conflicting option: ' . $option . '.');
        }
    }
    unset($definitions[MigrationService::class]);
    foreach (['Create' => 'create', 'Down' => 'down', 'History' => 'history', 'New' => 'new', 'Redo' => 'redo', 'Update' => 'up'] as $class => $name) {
        $definitions['Yiisoft\\Db\\Migration\\Command\\' . $class . 'Command']
            = static fn(CommandFactory $factory): DatabaseCommand => $factory->create($name);
    }
}

return $definitions;
