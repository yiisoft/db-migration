<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Command\Paths;

use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Yii\Console\ExitCode;

final class ListTablesCommandTest extends PathsCommandTest
{
    public function testExecute(): void
    {
        $this->createTable('tableOne', ['col' => 'text']);

        $this->assertEquals(ExitCode::OK, $this->getCommand()->execute([]));
    }

    private function getCommand(): CommandTester
    {
        return new CommandTester(
            $this->getApplication()->find('database/list')
        );
    }
}
