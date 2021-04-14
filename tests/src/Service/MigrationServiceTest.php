<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Service;

use Yiisoft\Db\Exception\InvalidConfigException;

final class MigrationServiceTest extends NamespaceMigrationServiceTest
{
//    public function testCompact(): void
//    {
//        $service = $this->getMigrationService();
//
//        $className = $this->createMigration('Create_Post', 'table', 'post', ['name:string']);
//
//        $service->compact(true);
//        $migration = $service->createMigration($className);
//        $service->getMigrationHistory();
//
//        ob_start();
//        $service->up($migration);
//        $output = ob_get_clean();
//
//        $this->assertEmpty($output);
//    }

    public function testUseTablePrefix(): void
    {
        $this->getMigrationService()->useTablePrefix(false);
        $this->assertFalse($this->getMigrationService()->getUseTablePrefix());
    }

    public function testVersion(): void
    {
        $this->assertSame('1.0', $this->getMigrationService()->version());
    }

    public function testGeneratorTemplateFile(): void
    {
        $this->getMigrationService()->generatorTemplateFile('hello', 'world');
        $this->assertSame('world', $this->getMigrationService()->getGeneratorTemplateFiles('hello'));
    }

    public function testNotExistsGeneratorTemplateFile(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('You must define a template to generate the migration.');
        $this->getMigrationService()->getGeneratorTemplateFiles('not-exists');
    }

    public function testGetNewMigrations(): void
    {
        $this->createMigration('Create_Post', 'table', 'post', ['name:string']);
        $this->applyNewMigrations();
        $this->createMigration('Create_User', 'table', 'user', ['name:string']);

        $this->getMigrationService()->updateNamespaces([
            $this->getNamespace(),
            'Yiisoft\\Yii\Db\\Migration\\TestsRuntime\\NotExists',
        ]);

        $migrations = $this->getMigrationService()->getNewMigrations();

        $this->assertCount(1, $migrations);
    }
}
