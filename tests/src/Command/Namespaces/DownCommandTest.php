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

        $this->assertStringContainsString(' 1 migration was reverted.', $output);

        $this->assertNotExistsTables('user');
        $this->assertExistsTables('post');
    }

    public function testExecuteAgain(): void
    {
        $command = $this->getCommand();

        $this->assertEquals(ExitCode::UNSPECIFIED_ERROR, $command->execute([]));

        $output = $command->getDisplay(true);

        $this->assertStringContainsString('Apply a new migration to run this command.', $output);
    }

    public function dataIncorrectLimit(): array
    {
        return [
            'negative' => [-1],
            'zero' => [0],
        ];
    }

    /**
     * @dataProvider dataIncorrectLimit
     */
    public function testIncorrectLimit(int $limit): void
    {
        $command = $this->getCommand();

        $exitCode = $command->execute(['-l' => $limit]);

        $this->assertSame(ExitCode::DATAERR, $exitCode);
    }

    public function testLimit(): void
    {
        $this->createMigration('Create_Post', 'table', 'post', ['name:string']);
        $this->createMigration('Create_User', 'table', 'user', ['name:string']);
        $this->createMigration('Create_Tag', 'table', 'tag', ['name:string']);
        $this->applyNewMigrations();

        $command = $this->getCommand();

        $exitCode = $command->execute(['-l' => '2']);
        $output = $command->getDisplay(true);

        $this->assertSame(ExitCode::OK, $exitCode);
        $this->assertStringContainsString('[OK] 2 migrations were reverted.', $output);
    }

    public function testFiled(): void
    {
        $this->createMigration('Create_Ship', 'table', 'ship', ['name:string'], function ($content) {
            return str_replace('RevertibleMigrationInterface', 'MigrationInterface', $content);
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
