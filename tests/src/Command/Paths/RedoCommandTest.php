<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Command\Paths;

use RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Yii\Console\ExitCode;

final class RedoCommandTest extends PathsCommandTest
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

        $this->assertStringContainsString('2 migrations were redone.', $output);
        $this->assertExistsTables('post', 'user');
    }

    public function testLimit(): void
    {
        $this->createMigration('Create_Post', 'table', 'post', ['name:string']);
        $this->createMigration('Create_User', 'table', 'user', ['name:string']);
        $this->applyNewMigrations();

        $command = $this->getCommand()->setInputs(['yes']);

        $exitCode = $command->execute(['-l' => '1']);
        $output = $command->getDisplay(true);

        $this->assertSame(ExitCode::OK, $exitCode);
        $this->assertStringContainsString('CreateUser', $output);
        $this->assertStringContainsString(' 1 migration was redone.', $output);
    }

    public function testIncorrectLimit(): void
    {
        $command = $this->getCommand();

        $exitCode = $command->execute(['-l' => -1]);

        $this->assertSame(ExitCode::DATAERR, $exitCode);
    }

    public function testWithoutNewMigrations(): void
    {
        $command = $this->getCommand();

        $exitCode = $command->execute([]);

        $this->assertSame(ExitCode::UNSPECIFIED_ERROR, $exitCode);
    }

    public function testFiledDown(): void
    {
        $this->createMigration('Create_Chunk', 'table', 'chunk', ['name:string'], function ($content) {
            return str_replace('RevertibleMigrationInterface', 'MigrationInterface', $content);
        });
        $this->applyNewMigrations();

        $command = $this->getCommand();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches(
            '/Migration M\d+CreateChunk does not implement RevertibleMigrationInterface\./'
        );
        $command->execute([]);
    }

    private function getCommand(): CommandTester
    {
        return new CommandTester(
            $this->getApplication()->find('migrate/redo')
        );
    }
}
