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
final class CreateJunctionCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        /** Set path for generate migration */
        $this->migrationService->createPath($this->getGeneratedMigrationFolder());
    }

    protected function tearDown(): void
    {
        $this->removeFiles($this->getGeneratedMigrationFolder());

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
                '--command' => 'junction',
                '--and' => 'tag',
                '--fields' => 'created_at:dateTime'
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
 * Handles the creation of table `post_tag`.
 * Has foreign keys to the tables:
 *
 * - `{{%post}}`
 * - `{{%tag}}`
 */
final class $file extends Migration
{
    public function up(): void
    {
        \$this->createTable('post_tag', [
            'post_id' => \$this->integer(),
            'tag_id' => \$this->integer(),
            'created_at' => \$this->dateTime(),
            'PRIMARY KEY(post_id, tag_id)',
        ]);

        // creates index for column `post_id`
        \$this->createIndex(
            'idx-post_tag-post_id',
            'post_tag',
            'post_id'
        );

        // add foreign key for table `{{%post}}`
        \$this->addForeignKey(
            'fk-post_tag-post_id',
            'post_tag',
            'post_id',
            '{{%post}}',
            'id',
            'CASCADE'
        );

        // creates index for column `tag_id`
        \$this->createIndex(
            'idx-post_tag-tag_id',
            'post_tag',
            'tag_id'
        );

        // add foreign key for table `{{%tag}}`
        \$this->addForeignKey(
            'fk-post_tag-tag_id',
            'post_tag',
            'tag_id',
            '{{%tag}}',
            'id',
            'CASCADE'
        );
    }

    public function down(): void
    {
        // drops foreign key for table `{{%post}}`
        \$this->dropForeignKey(
            'fk-post_tag-post_id',
            'post_tag'
        );

        // drops index for column `post_id`
        \$this->dropIndex(
            'idx-post_tag-post_id',
            'post_tag'
        );

        // drops foreign key for table `{{%tag}}`
        \$this->dropForeignKey(
            'fk-post_tag-tag_id',
            'post_tag'
        );

        // drops index for column `tag_id`
        \$this->dropIndex(
            'idx-post_tag-tag_id',
            'post_tag'
        );

        \$this->dropTable('post_tag');
    }
}

EOF;
        $generated = file_get_contents($this->getGeneratedMigrationFolder() . $file . '.php');
        $this->assertEqualsWithoutLE($generated, $expectedPhp);
    }
}
