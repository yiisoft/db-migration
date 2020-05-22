<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Namespaces;

use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Db\Migration\Tests\TestCase;

use function explode;
use function trim;

/**
 * @group namespaces
 */
final class NewCommandTest extends TestCase
{
    private string $namespace = 'Yiisoft\\Yii\Db\\Migration\\Build';
    private string $pathAliases = '';

    protected function setUp(): void
    {
        parent::setUp();

        /** Set list namespace for update migrations */
        $this->migrationService->updateNamespace([$this->namespace, 'Yiisoft\\Yii\\Db\\Migration']);
        $this->pathAliases = '@' . str_replace('\\', '/', $this->namespace);
    }

    protected function tearDown(): void
    {
        $this->removeFiles($this->consoleHelper->getPathFromNameSpace($this->pathAliases));

        parent::tearDown();
    }

    public function testExecute(): void
    {
        $command = $this->application->find('migrate/new');

        $commandNew = new CommandTester($command);

        $this->assertEquals(ExitCode::OK, $commandNew->execute([]));

        $output = $commandNew->getDisplay(true);

        $words = explode("\n", $output);

        foreach ($words as $word) {
            if (!empty($word)) {
                $word = '@' . str_replace('\\', '/', trim($word));
                $this->assertFileExists($this->consoleHelper->getPathFromNameSpace($word) . '.php');
            }
        }
    }
}
