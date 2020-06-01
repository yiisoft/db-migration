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
final class CreateDropTableCommandTest extends TestCase
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
                'name' => 'post',
                '--command' => 'dropTable',
                '--fields' => 'title:string(12):notNull:unique,body:text'
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
 * Handles the dropping of table `post`.
 */
final class $file extends Migration
{
    public function up(): void
    {
        \$this->dropTable('post');
    }

    public function down(): void
    {
        \$this->createTable('post', [
            'id' => \$this->primaryKey(),
            'title' => \$this->string(12)->notNull()->unique(),
            'body' => \$this->text(),
        ]);
    }
}

EOF;
        $generated = file_get_contents(
            $this->consoleHelper->getPathFromNameSpace($this->pathAliases) . '/' . $file . '.php'
        );
        $this->assertEqualsWithoutLE($generated, $expectedPhp);
    }
}
