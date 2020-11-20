<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Paths;

use function explode;
use function file_get_contents;
use function substr;

use Symfony\Component\Console\Tester\CommandTester;
use function trim;
use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Db\Migration\Tests\TestCase;

/**
 * @group paths
 */
final class CreateDropColumnCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        /** Set path for generate migration */
        $this->migrationService->createPath('@yiisoft/yii/db/migration/migration');
    }

    protected function tearDown(): void
    {
        $this->removeFiles($this->aliases->get('@yiisoft/yii/db/migration/migration'));

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
                '--command' => 'dropColumn',
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

use Yiisoft\Yii\Db\Migration\Migration;

/**
 * Handles dropping columns from table `post`.
 */
final class $file extends Migration
{
    public function up(): void
    {
        \$this->dropColumn('post', 'position');
    }

    public function down(): void
    {
        \$this->addColumn('post', 'position', \$this->integer());
    }
}

EOF;
        $generated = file_get_contents($this->aliases->get('@yiisoft/yii/db/migration/migration/' . $file . '.php'));
        $this->assertEqualsWithoutLE($generated, $expectedPhp);
    }
}
