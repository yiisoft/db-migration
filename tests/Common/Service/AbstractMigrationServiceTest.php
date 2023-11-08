<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Common\Service;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Yiisoft\Db\Migration\Migrator;
use Yiisoft\Db\Migration\Service\MigrationService;
use Yiisoft\Db\Migration\Tests\Support\Helper\MigrationHelper;

use function dirname;

abstract class AbstractMigrationServiceTest extends TestCase
{
    protected ContainerInterface $container;

    public function testVersion(): void
    {
        $service = $this->container->get(MigrationService::class);

        $this->assertSame('1.0', $service->version());
    }

    public function testGetNewMigrationsWithNotExistNamespace(): void
    {
        MigrationHelper::useMigrationsNamespace($this->container);

        $className = MigrationHelper::createMigration(
            $this->container,
            'Create_Post',
            'table',
            'post',
            ['name:string(50)'],
        );
        $this->container->get(Migrator::class)->up(new $className());

        $className = MigrationHelper::createMigration(
            $this->container,
            'Create_User',
            'table',
            'user',
            ['name:string(32)'],
        );

        $service = $this->container->get(MigrationService::class);

        $service->updateNamespaces([
            MigrationHelper::NAMESPACE,
            'Yiisoft\\Db\\Migration\\TestsRuntime\\NotExists',
        ]);

        $migrations = $service->getNewMigrations();

        $this->assertSame([$className], $migrations);
    }

    public function testGetNamespacesFromPathForNoHavingNamespacePath(): void
    {
        $migrationService = $this->container->get(MigrationService::class);

        $getNamespaceFromPath = new \ReflectionMethod($migrationService, 'getNamespacesFromPath');
        $getNamespaceFromPath->setAccessible(true);

        // No having namespace path
        $path = dirname(__DIR__, 3) . '/config';

        $this->assertSame([], $getNamespaceFromPath->invoke($migrationService, $path));
    }

    /**
     * Test MigrationService::getNamespacesFromPath() returns namespaces corresponding to the longest subdirectory of a path.
     * One path can match to several namespaces.
     */
    public function testGetNamespacesFromPathForLongestPath(): void
    {
        $migrationService = $this->container->get(MigrationService::class);

        $getNamespaceFromPath = new \ReflectionMethod($migrationService, 'getNamespacesFromPath');
        $getNamespaceFromPath->setAccessible(true);

        /**
         * Path corresponding to three namespaces:
         * `Yiisoft\\Db\\Migration\\Tests\\`
         * `Yiisoft\\Db\\Migration\\Tests\\Support\\`
         * `Yiisoft\\Db\\Migration\\Tests\\ForTest\\`
         */
        $path = dirname(__DIR__, 2) . '/Support/Migrations';

        $this->assertSame(
            ['Yiisoft\Db\Migration\Tests\Support\Migrations', 'Yiisoft\Db\Migration\Tests\ForTest\Migrations'],
            $getNamespaceFromPath->invoke($migrationService, $path),
        );
    }

    public function testFilterMigrationsWithoutNamespace(): void
    {
        $migrationService = $this->container->get(MigrationService::class);

        $this->assertSame([], $migrationService->filterMigrations(['ClassNameWithoutNamespace']));
    }
}
