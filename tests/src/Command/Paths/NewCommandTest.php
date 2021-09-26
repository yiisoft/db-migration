<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Command\Paths;

use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Yii\Console\ExitCode;

final class NewCommandTest extends PathsCommandTest
{
    public function testExecute(): void
    {
        $this->createMigration('Create_Post', 'table', 'post', ['name:string']);
        $this->createMigration('Create_User', 'table', 'user', ['name:string']);

        $command = $this->getCommand();

        $exitCode = $command->execute([]);
        $output = $command->getDisplay(true);

        $classNames = [];
        foreach (['CreatePost', 'CreateUser'] as $name) {
            preg_match_all('~M\d+' . $name . '$~m', $output, $matches);
            if (isset($matches[0][0])) {
                $classNames[] = $matches[0][0];
            }
        }

        $this->assertSame(ExitCode::OK, $exitCode);
        $this->assertStringContainsString('Found 2 new migrations:', $output);
        $this->assertCount(2, $classNames);
        $this->assertFileExists($this->getPath() . '/' . $classNames[0] . '.php');
        $this->assertFileExists($this->getPath() . '/' . $classNames[1] . '.php');
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

    public function testCountMigrationsMoreLimit(): void
    {
        $this->createMigration('Create_Post', 'table', 'post', ['name:string']);
        $this->createMigration('Create_User', 'table', 'user', ['name:string']);
        $this->createMigration('Create_Topic', 'table', 'topic', ['name:string']);

        $command = $this->getCommand();

        $exitCode = $command->execute(['-l' => 2]);

        $this->assertSame(ExitCode::OK, $exitCode);
    }

    private function getCommand(): CommandTester
    {
        return new CommandTester(
            $this->getApplication()->find('migrate/new')
        );
    }
}
