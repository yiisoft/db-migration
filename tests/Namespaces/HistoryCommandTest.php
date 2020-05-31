<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Namespaces;

use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Db\Migration\Tests\TestCase;

/**
 * @group namespaces
 */
final class HistoryCommandTest extends TestCase
{
    private string $namespace = 'Yiisoft\\Yii\Db\\Migration\\Tests\\Build';

    protected function setUp(): void
    {
        parent::setUp();

        /** Set list namespace for update migrations */
        $this->migrationService->updateNamespace([$this->namespace, 'Yiisoft\\Yii\\Db\\Migration']);
    }

    public function testExecute(): void
    {
        $command = $this->application->find('migrate/history');

        $commandHistory = new CommandTester($command);

        $this->assertEquals(ExitCode::OK, $commandHistory->execute([]));

        $output = $commandHistory->getDisplay(true);

        $this->assertMatchesRegularExpression('/M\d+[a-z_]/i', $output);
    }
}
