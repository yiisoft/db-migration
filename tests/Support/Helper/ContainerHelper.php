<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Support\Helper;

use Psr\Container\ContainerInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Injector\Injector;
use Yiisoft\Test\Support\Container\Exception\NotFoundException;
use Yiisoft\Db\Migration\Command\CreateCommand;
use Yiisoft\Db\Migration\Command\DownCommand;
use Yiisoft\Db\Migration\Command\HistoryCommand;
use Yiisoft\Db\Migration\Command\NewCommand;
use Yiisoft\Db\Migration\Command\RedoCommand;
use Yiisoft\Db\Migration\Command\UpdateCommand;
use Yiisoft\Db\Migration\Informer\ConsoleMigrationInformer;
use Yiisoft\Db\Migration\Migrator;
use Yiisoft\Db\Migration\Runner\DownRunner;
use Yiisoft\Db\Migration\Runner\UpdateRunner;
use Yiisoft\Db\Migration\Service\Generate\CreateService;
use Yiisoft\Db\Migration\Service\MigrationService;

final class ContainerHelper
{
    /**
     * @throws NotFoundException
     */
    public static function get(ContainerInterface $container, string $id, ContainerConfig $config): object
    {
        return match ($id) {
            Injector::class => new Injector($container),
            UpdateRunner::class => new UpdateRunner(
                $container->get(Migrator::class),
            ),
            DownRunner::class => new DownRunner(
                $container->get(Migrator::class),
            ),
            Migrator::class => new Migrator(
                $container->get(ConnectionInterface::class),
                $container->get(ConsoleMigrationInformer::class),
            ),
            MigrationService::class => new MigrationService(
                $container->get(ConnectionInterface::class),
                $container->get(Injector::class),
                $container->get(Migrator::class),
            ),
            CreateService::class => new CreateService(
                $container->get(ConnectionInterface::class),
                $config->useTablePrefix,
            ),
            ConsoleMigrationInformer::class => new ConsoleMigrationInformer(),
            CreateCommand::class => new CreateCommand(
                $container->get(CreateService::class),
                $container->get(MigrationService::class),
                $container->get(Migrator::class),
            ),
            UpdateCommand::class => new UpdateCommand(
                $container->get(UpdateRunner::class),
                $container->get(MigrationService::class),
                $container->get(Migrator::class),
            ),
            DownCommand::class => new DownCommand(
                $container->get(DownRunner::class),
                $container->get(MigrationService::class),
                $container->get(Migrator::class),
            ),
            NewCommand::class => new NewCommand(
                $container->get(MigrationService::class),
                $container->get(Migrator::class),
            ),
            HistoryCommand::class => new HistoryCommand(
                $container->get(MigrationService::class),
                $container->get(Migrator::class),
            ),
            RedoCommand::class => new RedoCommand(
                $container->get(MigrationService::class),
                $container->get(Migrator::class),
                $container->get(DownRunner::class),
                $container->get(UpdateRunner::class),
            ),
            default => throw new NotFoundException($id),
        };
    }
}
