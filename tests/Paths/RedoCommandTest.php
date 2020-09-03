<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Paths;

use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Db\Migration\Tests\TestCase;

/**
 * @group paths
 */
final class RedoCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        /** Set list path for update migration */
        $this->migrationService->updatePath([$this->getMigrationFolder()]);
        $this->migrateUp();
    }

    public function testExecute(): void
    {
        $create = $this->application->find('migrate/redo');

        $commandRedo = new CommandTester($create);

        $commandRedo->setInputs(['yes']);

        $this->assertEquals(ExitCode::OK, $commandRedo->execute([]));

        $output = $commandRedo->getDisplay(true);

        $this->assertStringContainsString('2 migrations were redone.', $output);
        $this->assertNotEmpty($this->db->getSchema()->getTableSchema('department'));
        $this->assertNotEmpty($this->db->getSchema()->getTableSchema('student'));
    }
}
