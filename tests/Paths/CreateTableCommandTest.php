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
final class CreateTableCommandTest extends TestCase
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
                '--command' => 'table',
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
 * Handles the creation of table `post`.
 */
final class $file extends Migration
{
    public function up(): void
    {
        \$this->createTable('post', [
            'id' => \$this->primaryKey(),
        ]);
    }

    public function down(): void
    {
        \$this->dropTable('post');
    }
}

EOF;
        $generated = file_get_contents($this->aliases->get('@yiisoft/yii/db/migration/migration/' . $file . '.php'));
        $this->assertEqualsWithoutLE($generated, $expectedPhp);
    }

    public function testExecuteWithFields(): void
    {
        $command = $this->application->find('generate/create');

        $commandCreate = new CommandTester($command);

        $commandCreate->setInputs(['yes']);

        $this->assertEquals(
            ExitCode::OK,
            $commandCreate->execute([
                'name' => 'post',
                '--command' => 'table',
                '--fields' => 'title:string,body:text',
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
 * Handles the creation of table `post`.
 */
final class $file extends Migration
{
    public function up(): void
    {
        \$this->createTable('post', [
            'id' => \$this->primaryKey(),
            'title' => \$this->string(),
            'body' => \$this->text(),
        ]);
    }

    public function down(): void
    {
        \$this->dropTable('post');
    }
}

EOF;
        $generated = file_get_contents($this->aliases->get('@yiisoft/yii/db/migration/migration/' . $file . '.php'));
        $this->assertEqualsWithoutLE($generated, $expectedPhp);
    }

    public function testExecuteWithFieldsForeignKey(): void
    {
        $command = $this->application->find('generate/create');

        $commandCreate = new CommandTester($command);

        $commandCreate->setInputs(['yes']);

        $this->assertEquals(
            ExitCode::OK,
            $commandCreate->execute([
                'name' => 'post',
                '--command' => 'table',
                '--fields' => 'author_id:integer:notNull:foreignKey(user),category_id:integer:defaultValue(1)' .
                ':foreignKey,title:string,body:text',
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
 * Handles the creation of table `post`.
 * Has foreign keys to the tables:
 *
 * - `{{%user}}`
 * - `{{%category}}`
 */
final class $file extends Migration
{
    public function up(): void
    {
        \$this->createTable('post', [
            'id' => \$this->primaryKey(),
            'author_id' => \$this->integer()->notNull(),
            'category_id' => \$this->integer()->defaultValue(1),
            'title' => \$this->string(),
            'body' => \$this->text(),
        ]);

        // creates index for column `author_id`
        \$this->createIndex(
            'idx-post-author_id',
            'post',
            'author_id'
        );

        // add foreign key for table `{{%user}}`
        \$this->addForeignKey(
            'fk-post-author_id',
            'post',
            'author_id',
            '{{%user}}',
            'id',
            'CASCADE'
        );

        // creates index for column `category_id`
        \$this->createIndex(
            'idx-post-category_id',
            'post',
            'category_id'
        );

        // add foreign key for table `{{%category}}`
        \$this->addForeignKey(
            'fk-post-category_id',
            'post',
            'category_id',
            '{{%category}}',
            'id',
            'CASCADE'
        );
    }

    public function down(): void
    {
        // drops foreign key for table `{{%user}}`
        \$this->dropForeignKey(
            'fk-post-author_id',
            'post'
        );

        // drops index for column `author_id`
        \$this->dropIndex(
            'idx-post-author_id',
            'post'
        );

        // drops foreign key for table `{{%category}}`
        \$this->dropForeignKey(
            'fk-post-category_id',
            'post'
        );

        // drops index for column `category_id`
        \$this->dropIndex(
            'idx-post-category_id',
            'post'
        );

        \$this->dropTable('post');
    }
}

EOF;
        $generated = file_get_contents($this->aliases->get('@yiisoft/yii/db/migration/migration/' . $file . '.php'));
        $this->assertEqualsWithoutLE($generated, $expectedPhp);
    }
}
