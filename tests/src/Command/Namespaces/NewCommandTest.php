<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Command\Namespaces;

use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Yii\Console\ExitCode;

final class NewCommandTest extends NamespacesCommandTest
{
    public function testExecute(): void
    {
        $this->createMigration('Create_Post', 'table', 'post', ['name:string']);
        $this->createMigration('Create_User', 'table', 'user', ['name:string']);

        $command = $this->getCommand();

        $this->assertEquals(ExitCode::OK, $command->execute([]));

        $output = $command->getDisplay(true);

        $words = explode("\n", $output);

        $this->assertFileExists($this->getPath() . '/' . $this->getClassShortname($words[0]) . '.php');
        $this->assertFileExists($this->getPath() . '/' . $this->getClassShortname($words[1]) . '.php');
    }

    private function getCommand(): CommandTester
    {
        return new CommandTester(
            $this->getApplication()->find('migrate/new')
        );
    }

    private function getClassShortname(string $name): string
    {
        $name = trim($name);
        $chunks = explode('\\', $name);
        return array_pop($chunks);
    }
}
