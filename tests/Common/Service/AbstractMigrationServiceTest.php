<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Common\Service;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Yiisoft\Db\Migration\Migrator;
use Yiisoft\Db\Migration\Service\MigrationService;
use Yiisoft\Db\Migration\Tests\Support\Helper\MigrationHelper;

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

    public function testFilterMigrations(): void
    {
        $migrationService = $this->container->get(MigrationService::class);

        $classes = $migrationService->filterMigrations(
            ['Yiisoft\\Db\\Migration\\TestsRuntime\\Migrations\\M231015155500ExecuteSql'],
            [],
            [__DIR__], // not matching path
        );

        $this->assertSame([], $classes);
    }
}
