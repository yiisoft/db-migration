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

    public function testFailMigrate(): void
    {
        $this->createMigration(
            'Create_Fail_Post',
            'table',
            'post',
            ['name:string'],
            static fn (string $content): string => str_replace(
                'CreateFailPost implements',
                'CreateFailPost2 implements',
                $content
            )
        );

        $command = $this->getCommand();

        $exitCode = $command->execute([]);
        $output = $command->getDisplay(true);

        $this->assertSame(ExitCode::UNSPECIFIED_ERROR, $exitCode);
        $this->assertStringContainsString('0 from 1 migrations were applied.', $output);
        $this->assertStringContainsString('Migration failed. The rest of the migrations are canceled.', $output);
    }

    private function getCommand(): CommandTester
    {
        return new CommandTester(
            $this->getApplication()->find('migrate/up')
        );
    }
}
