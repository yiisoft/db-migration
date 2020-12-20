<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Namespaces;

use function explode;
use function file_get_contents;
use function substr;

use Symfony\Component\Console\Tester\CommandTester;
use function trim;
use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Db\Migration\Tests\TestCase;

/**
 * @group namespaces
 */
final class CreateAddColumnCommandTest extends TestCase
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
        $this->removeFiles($this->consoleHelper->getPathFromNamespace($this->pathAliases));

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
                'name' => 'post',
                '--command' => 'addColumn',
                '--fields' => 'position:integer',
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
use Yiisoft\Yii\Db\Migration\RevertibleMigrationInterface;

/**
 * Handles adding columns to table `post`.
 */
final class $file extends Migration implements RevertibleMigrationInterface
{
    public function up(): void
    {
        \$this->addColumn('post', 'position', \$this->integer());
    }

    public function down(): void
    {
        \$this->dropColumn('post', 'position');
    }
}

EOF;
        $generated = file_get_contents(
            $this->consoleHelper->getPathFromNamespace($this->pathAliases) . '/' . $file . '.php'
        );
        $this->assertEqualsWithoutLE($generated, $expectedPhp);
    }
}
