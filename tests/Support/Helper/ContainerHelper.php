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
        switch ($id) {
            case Injector::class:
                return new Injector($container);

            case UpdateRunner::class:
                return new UpdateRunner(
                    $container->get(Migrator::class),
                );

            case DownRunner::class:
                return new DownRunner(
                    $container->get(Migrator::class),
                );

            case Migrator::class:
                return new Migrator(
                    $container->get(ConnectionInterface::class),
                    $container->get(ConsoleMigrationInformer::class),
                );

            case MigrationService::class:
                return new MigrationService(
                    $container->get(ConnectionInterface::class),
                    $container->get(Injector::class),
                    $container->get(Migrator::class),
                );

            case CreateService::class:
                return new CreateService(
                    $container->get(ConnectionInterface::class),
                    $config->useTablePrefix,
                );

            case ConsoleMigrationInformer::class:
                return new ConsoleMigrationInformer();

            case CreateCommand::class:
                return new CreateCommand(
                    $container->get(CreateService::class),
                    $container->get(MigrationService::class),
                    $container->get(Migrator::class),
                );

            case UpdateCommand::class:
                return new UpdateCommand(
                    $container->get(UpdateRunner::class),
                    $container->get(MigrationService::class),
                    $container->get(Migrator::class),
                );

            case DownCommand::class:
                return new DownCommand(
                    $container->get(DownRunner::class),
                    $container->get(MigrationService::class),
                    $container->get(Migrator::class),
                );

            case NewCommand::class:
                return new NewCommand(
                    $container->get(MigrationService::class),
                    $container->get(Migrator::class),
                );

            case HistoryCommand::class:
                return new HistoryCommand(
                    $container->get(MigrationService::class),
                    $container->get(Migrator::class),
                );

            case RedoCommand::class:
                return new RedoCommand(
                    $container->get(MigrationService::class),
                    $container->get(Migrator::class),
                    $container->get(DownRunner::class),
                    $container->get(UpdateRunner::class),
                );

            default:
                throw new NotFoundException($id);
        }
    }
}
