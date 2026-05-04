<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Common\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionMethod;
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

        $service->setSourceNamespaces([
            MigrationHelper::NAMESPACE,
            'Yiisoft\\Db\\Migration\\TestsRuntime\\NotExists',
        ]);

        $migrations = $service->getNewMigrations();

        $this->assertSame([$className], $migrations);
    }

    public static function getNewMigrationsDataProvider(): array
    {
        return [
            'empty' => [
                'expected' => [],
            ],
            'non exists newMigrationNamespace' => [
                'expected' => [],
                'newMigrationNamespace' => 'Yiisoft\Db\Migration\TestsRuntime\NotExists',
            ],
            'non exists newMigrationPath' => [
                'expected' => [],
                'newMigrationNamespace' => '',
                'newMigrationPath' => dirname(__DIR__, 2) . '/non-exists-directory',
            ],
            'non exists sourceNamespaces' => [
                'expected' => [],
                'newMigrationNamespace' => '',
                'newMigrationPath' => '',
                'sourceNamespaces' => ['Yiisoft\Db\Migration\TestsRuntime\NotExists'],
            ],
            'non exists sourcePaths' => [
                'expected' => [],
                'newMigrationNamespace' => '',
                'newMigrationPath' => '',
                'sourceNamespaces' => [],
                'sourcePaths' => [dirname(__DIR__, 2) . '/non-exists-directory'],
            ],
            'with newMigrationNamespace' => [
                'expected' => ['Yiisoft\Db\Migration\Tests\Support\MigrationsExtra\M231108183919Empty'],
                'newMigrationNamespace' => 'Yiisoft\Db\Migration\Tests\Support\MigrationsExtra',
            ],
            'with newMigrationPath' => [
                'expected' => ['M231108183919Empty'],
                'newMigrationNamespace' => '',
                'newMigrationPath' => dirname(__DIR__, 2) . '/Support/MigrationsExtra',
            ],
            'with sourceNamespaces with different paths' => [
                'expected' => [
                    'Yiisoft\Db\Migration\Tests\ForTest\Migrations\M231015155500ExecuteSql',
                    'Yiisoft\Db\Migration\Tests\ForTest\Migrations\M231017150317EmptyDown',
                    'Yiisoft\Db\Migration\Tests\Support\MigrationsExtra\M231108183919Empty',
                    'Yiisoft\Db\Migration\Tests\ForTest\Migrations\M250312122400ChangeDbPrefixUp',
                    'Yiisoft\Db\Migration\Tests\ForTest\Migrations\M250312122500ChangeDbPrefixDown',
                ],
                'newMigrationNamespace' => '',
                'newMigrationPath' => '',
                'sourceNamespaces' => [
                    'Yiisoft\Db\Migration\Tests\ForTest\Migrations',
                    'Yiisoft\Db\Migration\Tests\Support\MigrationsExtra',
                ],
            ],
            'with different sourceNamespaces with the same path' => [
                'expected' => [
                    'Yiisoft\Db\Migration\Tests\ForTest\MigrationsExtra\M231108183919Empty',
                    'Yiisoft\Db\Migration\Tests\Support\MigrationsExtra\M231108183919Empty',
                ],
                'newMigrationNamespace' => '',
                'newMigrationPath' => '',
                'sourceNamespaces' => [
                    'Yiisoft\Db\Migration\Tests\ForTest\MigrationsExtra',
                    'Yiisoft\Db\Migration\Tests\Support\MigrationsExtra',
                ],
            ],
            'with sourcePaths with different paths' => [
                'expected' => [
                    'M231108183919Empty',
                    'M231108183919Empty2',
                ],
                'newMigrationNamespace' => '',
                'newMigrationPath' => '',
                'sourceNamespaces' => [],
                'sourcePaths' => [
                    dirname(__DIR__, 2) . '/Support/MigrationsExtra',
                    dirname(__DIR__, 2) . '/Support/MigrationsExtra2',
                ],
            ],
            'with sourceNamespaces and sourcePaths with the same path' => [
                'expected' => [
                    'M231108183919Empty',
                    'Yiisoft\Db\Migration\Tests\Support\MigrationsExtra\M231108183919Empty',
                ],
                'newMigrationNamespace' => '',
                'newMigrationPath' => '',
                'sourceNamespaces' => ['Yiisoft\Db\Migration\Tests\Support\MigrationsExtra'],
                'sourcePaths' => [dirname(__DIR__, 2) . '/Support/MigrationsExtra'],
            ],
            'with sourceNamespaces and sourcePaths with different paths' => [
                'expected' => [
                    'M231108183919Empty2',
                    'Yiisoft\Db\Migration\Tests\Support\MigrationsExtra\M231108183919Empty',
                ],
                'newMigrationNamespace' => '',
                'newMigrationPath' => '',
                'sourceNamespaces' => ['Yiisoft\Db\Migration\Tests\Support\MigrationsExtra'],
                'sourcePaths' => [dirname(__DIR__, 2) . '/Support/MigrationsExtra2'],
            ],
            'with newMigrationNamespace and sourceNamespaces with the same path' => [
                'expected' => ['Yiisoft\Db\Migration\Tests\Support\MigrationsExtra\M231108183919Empty'],
                'newMigrationNamespace' => 'Yiisoft\Db\Migration\Tests\Support\MigrationsExtra',
                'newMigrationPath' => '',
                'sourceNamespaces' => ['Yiisoft\Db\Migration\Tests\Support\MigrationsExtra'],
            ],
            'with newMigrationPath and sourcePaths with the same path' => [
                'expected' => ['M231108183919Empty'],
                'newMigrationNamespace' => '',
                'newMigrationPath' => dirname(__DIR__, 2) . '/Support/MigrationsExtra',
                'sourceNamespaces' => [],
                'sourcePaths' => [dirname(__DIR__, 2) . '/Support/MigrationsExtra'],
            ],
            'with newMigrationNamespace and sourcePaths with the same path' => [
                'expected' => [
                    'M231108183919Empty',
                    'Yiisoft\Db\Migration\Tests\Support\MigrationsExtra\M231108183919Empty',
                ],
                'newMigrationNamespace' => 'Yiisoft\Db\Migration\Tests\Support\MigrationsExtra',
                'newMigrationPath' => '',
                'sourceNamespaces' => [],
                'sourcePaths' => [dirname(__DIR__, 2) . '/Support/MigrationsExtra'],
            ],
            'with newMigrationNamespace and sourceNamespaces with different paths' => [
                'expected' => [
                    'Yiisoft\Db\Migration\Tests\Support\Migrations\M231015155500ExecuteSql',
                    'Yiisoft\Db\Migration\Tests\Support\Migrations\M231017150317EmptyDown',
                    'Yiisoft\Db\Migration\Tests\Support\MigrationsExtra\M231108183919Empty',
                    'Yiisoft\Db\Migration\Tests\Support\Migrations\M250312122400ChangeDbPrefixUp',
                    'Yiisoft\Db\Migration\Tests\Support\Migrations\M250312122500ChangeDbPrefixDown',
                ],
                'newMigrationNamespace' => 'Yiisoft\Db\Migration\Tests\Support\MigrationsExtra',
                'newMigrationPath' => '',
                'sourceNamespaces' => ['Yiisoft\Db\Migration\Tests\Support\Migrations'],
            ],
            'with newMigrationPath and sourceNamespaces with different paths' => [
                'expected' => [
                    'M231108183919Empty2',
                    'Yiisoft\Db\Migration\Tests\Support\MigrationsExtra\M231108183919Empty',
                ],
                'newMigrationNamespace' => '',
                'newMigrationPath' => dirname(__DIR__, 2) . '/Support/MigrationsExtra2',
                'sourceNamespaces' => ['Yiisoft\Db\Migration\Tests\Support\MigrationsExtra'],
            ],
        ];
    }

    #[DataProvider('getNewMigrationsDataProvider')]
    public function testGetNewMigrations(
        array $expected,
        string $newMigrationNamespace = '',
        string $newMigrationPath = '',
        array $sourceNamespaces = [],
        array $sourcePaths = [],
    ): void {
        MigrationHelper::useMigrationsNamespace($this->container);

        $service = $this->container->get(MigrationService::class);
        $service->setNewMigrationNamespace($newMigrationNamespace);
        $service->setNewMigrationPath($newMigrationPath);
        $service->setSourceNamespaces($sourceNamespaces);
        $service->setSourcePaths($sourcePaths);

        $migrations = $service->getNewMigrations();

        $this->assertSame($expected, $migrations);
    }

    public function testGetNamespacesFromPathForNoHavingNamespacePath(): void
    {
        $migrationService = $this->container->get(MigrationService::class);

        $getNamespaceFromPath = new ReflectionMethod($migrationService, 'getNamespacesFromPath');

        // No having namespace path
        $path = dirname(__DIR__, 3) . '/config';

        $this->assertSame([], $getNamespaceFromPath->invoke($migrationService, $path));
    }

    public function testGetNamespacesFromPathForNoExistsDirectory(): void
    {
        $migrationService = $this->container->get(MigrationService::class);

        $getNamespaceFromPath = new ReflectionMethod($migrationService, 'getNamespacesFromPath');

        // There is a path to the namespace, but the directory does not exist
        $path = dirname(__DIR__, 2) . '/non-exists-directory';

        $this->assertSame([], $getNamespaceFromPath->invoke($migrationService, $path));
    }

    /**
     * Test MigrationService::getNamespacesFromPath() returns namespaces corresponding to the longest subdirectory of a path.
     * One path can match to several namespaces.
     */
    public function testGetNamespacesFromPathForLongestPath(): void
    {
        $migrationService = $this->container->get(MigrationService::class);

        $getNamespaceFromPath = new ReflectionMethod($migrationService, 'getNamespacesFromPath');

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
