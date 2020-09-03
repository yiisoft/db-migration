<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Paths;

use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Db\Migration\Tests\TestCase;

/**
 * @group paths
 */
final class DownCommandTest extends TestCase
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
        $create = $this->application->find('migrate/down');

        $commandDown = new CommandTester($create);

        $commandDown->setInputs(['yes']);

        $this->assertEquals(ExitCode::OK, $commandDown->execute([]));

        $output = $commandDown->getDisplay(true);

        $this->assertStringContainsString('2 migrations were reverted.', $output);

        $this->assertEmpty($this->db->getSchema()->getTableSchema('department'));
        $this->assertEmpty($this->db->getSchema()->getTableSchema('student'));
    }

    public function testExecuteAgain(): void
    {
        $create = $this->application->find('migrate/down');

        $commandDown = new CommandTester($create);
        $commandDown->execute([]);

        $this->assertEquals(ExitCode::UNSPECIFIED_ERROR, $commandDown->execute([]));

        $output = $commandDown->getDisplay(true);

        $this->assertStringContainsString('Apply a new migration to run this command.', $output);
    }
}
