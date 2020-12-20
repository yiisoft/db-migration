<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Command\Namespaces;

use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Yii\Console\ExitCode;

final class DownCommandTest extends NamespacesCommandTest
{
    public function testExecute(): void
    {
        $this->createMigration('Create_Post', 'table', 'post', ['name:string']);
        $this->createMigration('Create_User', 'table', 'user', ['name:string']);
        $this->applyNewMigrations();

        $this->assertExistsTables('post', 'user');

        $command = $this->getCommand();

        $command->setInputs(['yes']);

        $this->assertEquals(ExitCode::OK, $command->execute([]));

        $output = $command->getDisplay(true);

        $this->assertStringContainsString('2 migrations were reverted.', $output);

        $this->assertNotExistsTables('post', 'user');
    }

    public function testExecuteAgain(): void
    {
        $command = $this->getCommand();

        $this->assertEquals(ExitCode::UNSPECIFIED_ERROR, $command->execute([]));

        $output = $command->getDisplay(true);

        $this->assertStringContainsString('Apply a new migration to run this command.', $output);
    }

    public function testIncorrectLimit(): void
    {
        $command = $this->getCommand();

        $exitCode = $command->execute(['-l' => -1]);

        $this->assertSame(ExitCode::DATAERR, $exitCode);
    }

    public function testFiled(): void
    {
        $this->createMigration('Create_Ship', 'table', 'ship', ['name:string'], function ($content) {
            return str_replace(' implements RevertibleMigrationInterface', '', $content);
        });
        $this->applyNewMigrations();

        $command = $this->getCommand();

        $exitCode = $command->execute([]);

        $this->assertSame(ExitCode::UNSPECIFIED_ERROR, $exitCode);
    }

    private function getCommand(): CommandTester
    {
        return new CommandTester(
            $this->getApplication()->find('migrate/down')
        );
    }
}
