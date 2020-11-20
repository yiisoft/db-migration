<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Paths;

use function explode;
use Symfony\Component\Console\Tester\CommandTester;
use function trim;

use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Db\Migration\Tests\TestCase;

/**
 * @group paths
 */
final class NewCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        /** Set list path for update migration */
        $this->migrationService->updatePath(['@yiisoft/yii/db/migration/migration']);
    }

    protected function tearDown(): void
    {
        $this->removeFiles($this->aliases->get('@yiisoft/yii/db/migration/migration'));

        parent::tearDown();
    }

    public function testExecute(): void
    {
        $command = $this->application->find('migrate/new');

        $commandNew = new CommandTester($command);

        $this->assertEquals(ExitCode::OK, $commandNew->execute([]));

        $output = $commandNew->getDisplay(true);

        $words = explode("\n", $output);

        $this->assertFileExists($this->aliases->get('@yiisoft/yii/db/migration/migration') . DIRECTORY_SEPARATOR . trim($words[0]) . '.php');
        $this->assertFileExists($this->aliases->get('@yiisoft/yii/db/migration/migration') . DIRECTORY_SEPARATOR . trim($words[1]) . '.php');
    }
}
