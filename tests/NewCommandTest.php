<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests;

use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Yii\Console\ExitCode;

use function explode;
use function trim;

final class NewCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->removeFiles($this->aliases->get('@migration'));

        parent::tearDown();
    }

    public function testExecute(): void
    {
        $command = $this->application->find('migrate/new');

        $commandNew = new CommandTester($command);

        $this->assertEquals(ExitCode::OK, $commandNew->execute([]));

        $output = $commandNew->getDisplay(true);
        $words = explode("\n", $output);

        $this->assertFileExists($this->aliases->get('@migration') . DIRECTORY_SEPARATOR . trim($words[0]) . '.php');
        $this->assertFileExists($this->aliases->get('@migration') . DIRECTORY_SEPARATOR . trim($words[1]) . '.php');
    }
}
