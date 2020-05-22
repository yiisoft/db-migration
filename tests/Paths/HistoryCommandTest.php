<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Paths;

use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Db\Migration\Tests\TestCase;

/**
 * @group paths
 */
final class HistoryCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        /** Set list path for update migration */
        $this->migrationService->updatePath(['@migration', '@root']);
    }

    public function testExecute(): void
    {
        $command = $this->application->find('migrate/history');

        $commandHistory = new CommandTester($command);

        $this->assertEquals(ExitCode::OK, $commandHistory->execute([]));

        $output = $commandHistory->getDisplay(true);

        $this->assertStringContainsString('>>> List migration history.', $output);
    }
}
