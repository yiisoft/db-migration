<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests;

use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Yii\Console\ExitCode;

final class HistoryCommandTest extends TestCase
{
    public function testExecute(): void
    {
        $command = $this->application->find('migrate/history');

        $commandHistory = new CommandTester($command);

        $this->assertEquals(ExitCode::OK, $commandHistory->execute([]));

        $output = $commandHistory->getDisplay(true);

        $this->assertStringContainsString('>>> List migration history.', $output);
    }
}
