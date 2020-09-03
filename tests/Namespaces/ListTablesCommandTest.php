<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Namespaces;

use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Db\Migration\Tests\TestCase;

/**
 * @group namespaces
 */
final class ListTablesCommandTest extends TestCase
{
    private string $namespace = 'Yiisoft\\Yii\Db\\Migration\\Tests\\NamespaceMigration';

    protected function setUp(): void
    {
        parent::setUp();

        /** Set list namespace for update migrations */
        $this->migrationService->updateNamespace([$this->namespace]);
        $this->migrateUp();
    }

    public function testExecute(): void
    {
        $create = $this->application->find('database/list');

        $commandListTables = new CommandTester($create);

        $this->assertEquals(ExitCode::OK, $commandListTables->execute([]));
    }
}
