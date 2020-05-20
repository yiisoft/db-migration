<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests;

use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Yii\Console\ExitCode;

final class ListTablesCommandTest extends TestCase
{
    public function testExecute(): void
    {
        $create = $this->application->find('database/list');

        $commandListTables = new CommandTester($create);

        $this->assertEquals(ExitCode::OK, $commandListTables->execute([]));
    }
}
