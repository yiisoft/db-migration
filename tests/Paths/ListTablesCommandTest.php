<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Paths;

use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Db\Migration\Tests\TestCase;

/**
 * @group paths
 */
final class ListTablesCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->migrationService->updatePath([$this->getMigrationFolder()]);
        $this->migrateUp();
    }

    public function testExecute(): void
    {
        $create = $this->application->find('database/list');

        $commandListTables = new CommandTester($create);

        $this->assertEquals(ExitCode::OK, $commandListTables->execute([]));
    }
}
