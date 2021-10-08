<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Service;

use PHPUnit\Framework\TestCase;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Injector\Injector;
use Yiisoft\Yii\Db\Migration\Migrator;
use Yiisoft\Yii\Db\Migration\Service\MigrationService;
use Yiisoft\Yii\Db\Migration\Tests\Support\Helper\MigrationHelper;
use Yiisoft\Yii\Db\Migration\Tests\Support\Helper\SqLiteHelper;

final class MigrationServiceTest extends TestCase
{
    public function testUseTablePrefix(): void
    {
        $container = SqLiteHelper::createContainer();

        $service = new MigrationService(
            $container->get(Aliases::class),
            $container->get(ConnectionInterface::class),
            $container->get(Injector::class),
            $container->get(Migrator::class),
        );

        $this->assertTrue($service->getUseTablePrefix());

        $service->useTablePrefix(false);

        $this->assertFalse($service->getUseTablePrefix());
    }

    public function testVersion(): void
    {
        $service = SqLiteHelper::createContainer()->get(MigrationService::class);

        $this->assertSame('1.0', $service->version());
    }

    public function testGeneratorTemplateFile(): void
    {
        $service = SqLiteHelper::createContainer()->get(MigrationService::class);

        $service->generatorTemplateFile('hello', '/templates/hello.php');

        $this->assertSame('/templates/hello.php', $service->getGeneratorTemplateFiles('hello'));
    }

    public function testNotExistsGeneratorTemplateFile(): void
    {
        $service = SqLiteHelper::createContainer()->get(MigrationService::class);

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('You must define a template to generate the migration.');
        $service->getGeneratorTemplateFiles('not-exists');
    }

    public function testGetNewMigrationsWithNotExistNamespace(): void
    {
        $container = SqLiteHelper::createContainer();
        MigrationHelper::useMigrationsNamespace($container);
        SqLiteHelper::clearDatabase($container);

        $className = MigrationHelper::createMigration(
            $container,
            'Create_Post',
            'table',
            'post',
            ['name:string(50)'],
        );
        $container->get(Migrator::class)->up(new $className);

        $className = MigrationHelper::createMigration(
            $container,
            'Create_User',
            'table',
            'user',
            ['name:string(32)'],
        );

        $service = $container->get(MigrationService::class);

        $service->updateNamespaces([
            MigrationHelper::NAMESPACE,
            'Yiisoft\\Yii\Db\\Migration\\TestsRuntime\\NotExists',
        ]);

        $migrations = $service->getNewMigrations();

        $this->assertSame([$className], $migrations);
    }
}
