<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Namespaces;

use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Db\Migration\Tests\TestCase;

/**
 * @group namespaces
 */
final class RedoCommandTest extends TestCase
{
    private string $namespace = 'Yiisoft\\Yii\Db\\Migration\\Tests\\Build';

    protected function setUp(): void
    {
        parent::setUp();

        /** Set namespace for generate migration */
        $this->migrationService->createNamespace($this->namespace);

        /** Set list namespace for update migrations */
        $this->migrationService->updateNamespace([$this->namespace, 'Yiisoft\\Yii\\Db\\Migration']);
    }

    public function testExecute(): void
    {
        $create = $this->application->find('migrate/redo');

        $commandRedo = new CommandTester($create);

        $commandRedo->setInputs(['yes']);

        $this->assertEquals(ExitCode::OK, $commandRedo->execute([]));

        $output = $commandRedo->getDisplay(true);

        $this->assertStringContainsString('2 migrations were redone.', $output);
        $this->assertNotEmpty($this->db->getSchema()->getTableSchema('department'));
        $this->assertNotEmpty($this->db->getSchema()->getTableSchema('student'));
    }
}
