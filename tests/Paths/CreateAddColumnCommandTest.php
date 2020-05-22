<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Paths;

use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Db\Migration\Tests\TestCase;

use function explode;
use function file_get_contents;
use function str_repeat;
use function substr;
use function trim;

/**
 * @group paths
 */
final class CreateAddColumnCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        /** Set path for generate migration */
        $this->migrationService->createPath('@migration');
    }

    protected function tearDown(): void
    {
        $this->removeFiles($this->aliases->get('@migration'));

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
            '--fields' => 'position:integer'
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

use Yiisoft\Yii\Db\Migration\Migration;

/**
 * Handles adding columns to table `post`.
 */
class $file extends Migration
{
    public function safeUp()
    {
        \$this->addColumn('post', 'position', \$this->integer());
    }

    public function safeDown()
    {
        \$this->dropColumn('post', 'position');
    }
}

EOF;
        $generated = file_get_contents($this->aliases->get('@migration/' . $file . '.php'));
        $this->assertEqualsWithoutLE($generated, $expectedPhp);
    }
}
