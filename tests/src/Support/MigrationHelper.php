<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Support;

use Psr\Container\ContainerInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Files\FileHelper;
use Yiisoft\Yii\Db\Migration\Service\MigrationService;

use function dirname;

final class MigrationHelper
{
    private const PATH_ALIAS = '@runtime/migration-path';
    public const NAMESPACE = 'Yiisoft\\Yii\Db\\Migration\\TestsRuntime\\MigrationNamespace';

    /**
     * @return string The migrations directory
     */
    public static function useMigrationsPath(ContainerInterface $container): string
    {
        $service = $container->get(MigrationService::class);

        $service->createPath(self::PATH_ALIAS);
        $service->updatePaths([self::PATH_ALIAS]);

        $path = $container->get(Aliases::class)->get(self::PATH_ALIAS);

        self::preparePath($path);

        return $path;
    }

    /**
     * @return string The migrations directory
     */
    public static function useMigrationsNamespace(ContainerInterface $container): string
    {
        $service = $container->get(MigrationService::class);

        $service->createNamespace(self::NAMESPACE);
        $service->updateNamespaces([self::NAMESPACE]);

        $path = dirname(__DIR__, 2) . '/runtime/MigrationNamespace';

        self::preparePath($path);

        return $path;
    }

    public static function findMigrationClassNameInOutput(string $output): string
    {
        $words = explode(' ', $output);

        foreach ($words as $word) {
            if (preg_match('/^\s*M\d{12}\D.*/', $word)) {
                return trim($word);
            }
        }

        return '';
    }

    private static function preparePath(string $path): void
    {
        file_exists($path)
            ? FileHelper::clearDirectory($path)
            : mkdir($path);
    }
}
