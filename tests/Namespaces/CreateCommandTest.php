<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Namespaces;

use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Db\Migration\Tests\TestCase;

use function explode;
use function file_get_contents;
use function str_repeat;
use function substr;
use function trim;

/**
 * @group namespaces
 */
final class CreateCommandTest extends TestCase
{
    private string $namespace = 'Yiisoft\\Yii\Db\\Migration\\Tests\\Build';
    private string $pathAliases = '';

    protected function setUp(): void
    {
        parent::setUp();

        /** Set namespace for generate migration */
        $this->migrationService->createNamespace($this->namespace);

        $this->pathAliases = '@' . str_replace('\\', '/', $this->namespace);
    }

    protected function tearDown(): void
    {
        $this->removeFiles($this->consoleHelper->getPathFromNameSpace($this->pathAliases));

        parent::tearDown();
    }

    public function testExecute(): void
    {
        $command = $this->application->find('generate/create');

        $commandCreate = new CommandTester($command);

        $commandCreate->setInputs(['yes']);

        $this->assertEquals(
            ExitCode::OK,
            $commandCreate->execute([
                'name' => 'post'
            ])
        );

        $output = $commandCreate->getDisplay(true);
        $words = explode(' ', $output);

        foreach ($words as $word) {
            $result = substr(trim($word), 0, 1);
            if ($result === 'M') {
                $file = trim($word);
            }
        }

        $this->assertStringContainsString('Create new migration y/n:', $output);

        $expectedPhp = <<<EOF
<?php

declare(strict_types=1);

namespace $this->namespace;

use Yiisoft\Yii\Db\Migration\Migration;

/**
 * Class $file
 */
class $file extends Migration
{
    public function up(): void
    {

    }

    public function down(): void
    {
        throw new \RuntimeException('Cannot revert migration. down() is not implemented.');
    }
}

EOF;
        $generated = file_get_contents(
            $this->consoleHelper->getPathFromNameSpace($this->pathAliases) . '/' . $file . '.php'
        );
        $this->assertEqualsWithoutLE($generated, $expectedPhp);
    }

    public function testExecuteInputNamespaces(): void
    {
        /** Set path for generate migration */
        $this->migrationService->createPath('@migration');

        $command = $this->application->find('generate/create');

        $commandCreate = new CommandTester($command);

        $commandCreate->setInputs(['yes']);

        $this->assertEquals(
            ExitCode::OK,
            $commandCreate->execute([
                'name' => 'post',
                '--namespace' => $this->namespace
            ])
        );

        $output = $commandCreate->getDisplay(true);
        $words = explode(' ', $output);

        foreach ($words as $word) {
            $result = substr(trim($word), 0, 1);
            if ($result === 'M') {
                $file = trim($word);
            }
        }

        $this->assertStringContainsString('Create new migration y/n:', $output);

        $expectedPhp = <<<EOF
<?php

declare(strict_types=1);

namespace $this->namespace;

use Yiisoft\Yii\Db\Migration\Migration;

/**
 * Class $file
 */
class $file extends Migration
{
    public function up(): void
    {

    }

    public function down(): void
    {
        throw new \RuntimeException('Cannot revert migration. down() is not implemented.');
    }
}

EOF;
        $generated = file_get_contents(
            $this->consoleHelper->getPathFromNameSpace($this->pathAliases) . '/' . $file . '.php'
        );
        $this->assertEqualsWithoutLE($generated, $expectedPhp);
    }


    public function testExecuteNameException(): void
    {
        $command = $this->application->find('generate/create');

        $commandCreate = new CommandTester($command);

        $commandCreate->setInputs(['yes']);

        $this->assertEquals(
            ExitCode::DATAERR,
            $commandCreate->execute([
                'name' => 'post?'
            ])
        );
    }

    public function testExecuteCommandException(): void
    {
        $command = $this->application->find('generate/create');

        $commandCreate = new CommandTester($command);

        $commandCreate->setInputs(['yes']);

        $this->assertEquals(
            ExitCode::DATAERR,
            $commandCreate->execute([
                'name' => 'post',
                '--command' => 'noExist'
            ])
        );
    }

    public function testExecuteNameToLongException(): void
    {
        $command = $this->application->find('generate/create');

        $commandCreate = new CommandTester($command);

        $commandCreate->setInputs(['yes']);

        $this->assertEquals(
            ExitCode::DATAERR,
            $commandCreate->execute([
                'name' => str_repeat('x', 200)
            ])
        );
    }
}
