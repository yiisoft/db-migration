<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests;

use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Yii\Console\ExitCode;

final class RedoCommandTest extends TestCase
{
    public function testExecute(): void
    {
        $create = $this->application->find('migrate/redo');

        $commandRedo = new CommandTester($create);

        $commandRedo->setInputs(['yes']);

        $this->assertEquals(ExitCode::OK, $commandRedo->execute([]));

        $output = $commandRedo->getDisplay(true);

        $this->assertStringContainsString('>>> 2 migrations were redone.', $output);
        $this->assertNotEmpty($this->db->getSchema()->getTableSchema('department'));
        $this->assertNotEmpty($this->db->getSchema()->getTableSchema('student'));
    }
}
