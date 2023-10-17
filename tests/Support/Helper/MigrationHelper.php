<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Support\Helper;

use Closure;
use Psr\Container\ContainerInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Files\FileHelper;
use Yiisoft\Db\Migration\Migrator;
use Yiisoft\Db\Migration\Service\Generate\CreateService;
use Yiisoft\Db\Migration\Service\MigrationService;

use function dirname;

final class MigrationHelper
{
    public const PATH_ALIAS = '@runtime/migration-path';
    public const NAMESPACE = 'Yiisoft\\Db\\Migration\\Tests\\runtime\\MigrationNamespace';

    /**
     * @return string The migrations directory
     */
    public static function useMigrationsPath(ContainerInterface $container): string
    {
        $service = $container->get(MigrationService::class);

        $service->createPath(self::PATH_ALIAS);
        $service->updatePaths([self::PATH_ALIAS]);

        self::preparePaths($container);

        return self::getPathForMigrationPath($container);
    }

    /**
     * @return string The migrations directory
     */
    public static function useMigrationsNamespace(ContainerInterface $container): string
    {
        $service = $container->get(MigrationService::class);

        $service->createNamespace(self::NAMESPACE);
        $service->updateNamespaces([self::NAMESPACE]);

        self::preparePaths($container);

        return self::getPathForMigrationNamespace();
    }

    public static function findMigrationClassNameInOutput(string $output): string
    {
        preg_match('/.*\s+(M\d{12}\D\S*)\s+.*/m', $output, $matches);
        return $matches[1] ?? '';
    }

    public static function createMigration(
        ContainerInterface $container,
        string $name,
        string $command,
        string $table,
        array $fields = [],
        Closure|null $callback = null
    ): string {
        $migrationService = $container->get(MigrationService::class);
        $createService = $container->get(CreateService::class);
        $aliases = $container->get(Aliases::class);

        [$namespace, $className] = $migrationService->generateClassName(null, $name);

        $content = $createService->run(
            $command,
            $table,
            $className,
            $namespace,
            implode(',', $fields)
        );

        if ($callback) {
            $content = $callback($content);
        }

        file_put_contents(
            $aliases->get($migrationService->findMigrationPath($namespace)) . '/' . $className . '.php',
            $content
        );

        return $namespace === null
            ? $className
            : ($namespace . '\\' . $className);
    }

    public static function createAndApplyMigration(
        ContainerInterface $container,
        string $name,
        string $command,
        string $table,
        array $fields = [],
        ?Closure $callback = null
    ): string {
        $className = self::createMigration($container, $name, $command, $table, $fields, $callback);

        $migration = $container->get(MigrationService::class)->makeMigration($className);
        $container->get(Migrator::class)->up($migration);

        return $className;
    }

    public static function getPathForMigrationNamespace(): string
    {
        return dirname(__DIR__, 2) . '/runtime/MigrationNamespace';
    }

    private static function getPathForMigrationPath(ContainerInterface $container): string
    {
        return $container->get(Aliases::class)->get(self::PATH_ALIAS);
    }

    private static function preparePaths(ContainerInterface $container): void
    {
        $paths = [
            self::getPathForMigrationNamespace(),
            self::getPathForMigrationPath($container),
        ];

        foreach ($paths as $path) {
            file_exists($path)
                ? FileHelper::clearDirectory($path)
                : mkdir($path);
        }
    }

    public static function resetPathAndNamespace(ContainerInterface $container): void
    {
        $service = $container->get(MigrationService::class);

        $service->createPath('');
        $service->updatePaths([]);
        $service->createNamespace('');
        $service->updateNamespaces([]);
    }

    public static function clearHistory(ContainerInterface $container): void
    {
        $db = $container->get(ConnectionInterface::class);
        $migrator = $container->get(Migrator::class);
        $db->createCommand()->delete($migrator->getHistoryTable())->execute();
    }
}
