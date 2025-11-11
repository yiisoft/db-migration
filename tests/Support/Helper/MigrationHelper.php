<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Support\Helper;

use Closure;
use Psr\Container\ContainerInterface;
use Yiisoft\Files\FileHelper;
use Yiisoft\Db\Migration\Migrator;
use Yiisoft\Db\Migration\Service\Generate\CreateService;
use Yiisoft\Db\Migration\Service\MigrationService;

use function dirname;

final class MigrationHelper
{
    public const NAMESPACE = 'Yiisoft\\Db\\Migration\\Tests\\runtime\\MigrationNamespace';

    public static function getRuntimePath(): string
    {
        return dirname(__DIR__, 2) . '/runtime/migration-path';
    }

    /**
     * @return string The migrations directory
     */
    public static function useMigrationsPath(ContainerInterface $container): string
    {
        $service = $container->get(MigrationService::class);

        $service->setNewMigrationPath(self::getRuntimePath());
        $service->setSourcePaths([self::getRuntimePath()]);

        self::preparePaths();

        return self::getRuntimePath();
    }

    /**
     * @return string The migrations directory
     */
    public static function useMigrationsNamespace(ContainerInterface $container): string
    {
        $service = $container->get(MigrationService::class);

        $service->setNewMigrationNamespace(self::NAMESPACE);
        $service->setSourceNamespaces([self::NAMESPACE]);

        self::preparePaths();

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
        ?Closure $callback = null,
    ): string {
        $migrationService = $container->get(MigrationService::class);
        $createService = $container->get(CreateService::class);

        $namespace = $migrationService->getNewMigrationNamespace();
        $className = $migrationService->generateClassName($name);

        $content = $createService->run(
            $command,
            $table,
            $className,
            $namespace,
            implode(',', $fields),
        );

        if ($callback) {
            $content = $callback($content);
        }

        file_put_contents(
            $migrationService->findMigrationPath() . '/' . $className . '.php',
            $content,
        );

        return $namespace === ''
            ? $className
            : ($namespace . '\\' . $className);
    }

    public static function createAndApplyMigration(
        ContainerInterface $container,
        string $name,
        string $command,
        string $table,
        array $fields = [],
        ?Closure $callback = null,
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

    public static function resetPathAndNamespace(ContainerInterface $container): void
    {
        $service = $container->get(MigrationService::class);

        $service->setNewMigrationPath('');
        $service->setSourcePaths([]);
        $service->setNewMigrationNamespace('');
        $service->setSourceNamespaces([]);
    }

    private static function preparePaths(): void
    {
        $paths = [
            self::getPathForMigrationNamespace(),
            self::getRuntimePath(),
        ];

        foreach ($paths as $path) {
            file_exists($path)
                ? FileHelper::clearDirectory($path)
                : mkdir($path);
        }
    }
}
