<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Command\Namespaces;

use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Yii\Console\ExitCode;

final class UpdateCommandTest extends NamespacesCommandTest
{
    public function testWithoutUpdateNamespaces(): void
    {
        $this->getMigrationService()->updateNamespaces([]);

        $command = $this->getCommand();

        $exitCode = $command->execute([]);

        $this->assertSame(ExitCode::DATAERR, $exitCode);
    }

    public function testLimit(): void
    {
        $this->createMigration('Create_Post', 'table', 'post', ['name:string']);
        $this->createMigration('Create_User', 'table', 'user', ['name:string']);

        $command = $this->getCommand();

        $exitCode = $command->execute(['-l' => 1]);

        $this->assertSame(ExitCode::OK, $exitCode);
        $this->assertExistsTables('post');
        $this->assertNotExistsTables('user');
    }

    public function testNameLimit(): void
    {
        $this->createMigration('Create_Post_' . str_repeat('X', 200), 'table', 'post', ['name:string']);

        $command = $this->getCommand();

        $exitCode = $command->execute([]);

        $this->assertSame(ExitCode::UNSPECIFIED_ERROR, $exitCode);
    }

    private function getCommand(): CommandTester
    {
        return new CommandTester(
            $this->getApplication()->find('migrate/up')
        );
    }
}
