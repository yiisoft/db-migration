<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Widget;

use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Yii\Db\Migration\Tests\TestCase;

final class CreateCommandTest extends TestCase
{
    public function testExecute(): void
    {
        $command = $this->application->find('generate/create');

        $commandTester = new CommandTester($command);

        $commandTester->setInputs(['yes']);
        $commandTester->execute([
            'name' => 'post'
        ]);
        $output = $commandTester->getDisplay();
        $words = explode(' ', $output);

        foreach ($words as $word) {
            $result = substr($word, 1);
            if ($result = 'm') {
                $file = trim($word);
            }
        }

        $this->assertStringContainsString('Create new migration y/n:', $output);

        $expectedPhp = <<<EOF
<?php

declare(strict_types=1);

use Yiisoft\Migration\Migration;

/**
 * Class $file
 */
class $file extends Migration
{
    public function safeUp()
    {

    }

    public function safeDown()
    {
        echo "$file cannot be reverted.<br\>";

        return false;
    }
}

EOF;
        $generated = file_get_contents($this->aliases->get('@migration/' . $file . '.php'));
        $this->assertEqualsWithoutLE($generated, $expectedPhp);
    }

    public function testExecuteCommandTable(): void
    {
        $command = $this->application->find('generate/create');

        $commandTester = new CommandTester($command);

        $commandTester->setInputs(['yes']);
        $commandTester->execute([
            'name' => 'post',
            '--command' => 'table'
        ]);
        $output = $commandTester->getDisplay();
        $words = explode(' ', $output);

        foreach ($words as $word) {
            $result = substr($word, 1);
            if ($result = 'm') {
                $file = trim($word);
            }
        }

        $this->assertStringContainsString('Create new migration y/n:', $output);

        $expectedPhp = <<<EOF
<?php

declare(strict_types=1);

use Yiisoft\Migration\Migration;

/**
 * Handles the creation of table `post`.
 */
class $file extends Migration
{
    public function safeUp()
    {
        \$this->createTable('post', [
            'id' => \$this->primaryKey(),
        ]);
    }

    public function safeDown()
    {
        \$this->dropTable('post');
    }
}

EOF;
        $generated = file_get_contents($this->aliases->get('@migration/' . $file . '.php'));
        $this->assertEqualsWithoutLE($generated, $expectedPhp);
    }

    public function testExecuteCommandTableWithFields(): void
    {
        $command = $this->application->find('generate/create');

        $commandTester = new CommandTester($command);

        $commandTester->setInputs(['yes']);
        $commandTester->execute([
            'name' => 'post',
            '--command' => 'table',
            '--fields' => 'title:string,body:text'
        ]);
        $output = $commandTester->getDisplay();
        $words = explode(' ', $output);

        foreach ($words as $word) {
            $result = substr($word, 1);
            if ($result = 'm') {
                $file = trim($word);
            }
        }

        $this->assertStringContainsString('Create new migration y/n:', $output);

        $expectedPhp = <<<EOF
<?php

declare(strict_types=1);

use Yiisoft\Migration\Migration;

/**
 * Handles the creation of table `post`.
 */
class $file extends Migration
{
    public function safeUp()
    {
        \$this->createTable('post', [
            'id' => \$this->primaryKey(),
            'title' => \$this->string(),
            'body' => \$this->text(),
        ]);
    }

    public function safeDown()
    {
        \$this->dropTable('post');
    }
}

EOF;
        $generated = file_get_contents($this->aliases->get('@migration/' . $file . '.php'));
        $this->assertEqualsWithoutLE($generated, $expectedPhp);
    }

    public function testExecuteCommandTableWithFieldsForeingKey(): void
    {
        $command = $this->application->find('generate/create');

        $commandTester = new CommandTester($command);

        $commandTester->setInputs(['yes']);
        $commandTester->execute([
            'name' => 'post',
            '--command' => 'table',
            '--fields' => 'author_id:integer:notNull:foreignKey(user),category_id:integer:defaultValue(1)' .
                ':foreignKey,title:string,body:text'
        ]);
        $output = $commandTester->getDisplay();
        $words = explode(' ', $output);

        foreach ($words as $word) {
            $result = substr($word, 1);
            if ($result = 'm') {
                $file = trim($word);
            }
        }

        $this->assertStringContainsString('Create new migration y/n:', $output);

        $expectedPhp = <<<EOF
<?php

declare(strict_types=1);

use Yiisoft\Migration\Migration;

/**
 * Handles the creation of table `post`.
 * Has foreign keys to the tables:
 *
 * - `{{%user}}`
 * - `{{%category}}`
 */
class $file extends Migration
{
    public function safeUp()
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
            '{{%idx-post-author_id}}',
            'post',
            'author_id'
        );

        // add foreign key for table `{{%user}}`
        \$this->addForeignKey(
            '{{%fk-post-author_id}}',
            'post',
            'author_id',
            '{{%user}}',
            'id',
            'CASCADE'
        );

        // creates index for column `category_id`
        \$this->createIndex(
            '{{%idx-post-category_id}}',
            'post',
            'category_id'
        );

        // add foreign key for table `{{%category}}`
        \$this->addForeignKey(
            '{{%fk-post-category_id}}',
            'post',
            'category_id',
            '{{%category}}',
            'id',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        // drops foreign key for table `{{%user}}`
        \$this->dropForeignKey(
            '{{%fk-post-author_id}}',
            'post'
        );

        // drops index for column `author_id`
        \$this->dropIndex(
            '{{%idx-post-author_id}}',
            'post'
        );

        // drops foreign key for table `{{%category}}`
        \$this->dropForeignKey(
            '{{%fk-post-category_id}}',
            'post'
        );

        // drops index for column `category_id`
        \$this->dropIndex(
            '{{%idx-post-category_id}}',
            'post'
        );

        \$this->dropTable('post');
    }
}

EOF;
        $generated = file_get_contents($this->aliases->get('@migration/' . $file . '.php'));
        $this->assertEqualsWithoutLE($generated, $expectedPhp);
    }

    public function testExecuteCommandDropTableWithFields(): void
    {
        $command = $this->application->find('generate/create');

        $commandTester = new CommandTester($command);

        $commandTester->setInputs(['yes']);
        $commandTester->execute([
            'name' => 'post',
            '--command' => 'dropTable',
            '--fields' => 'title:string(12):notNull:unique,body:text'
        ]);
        $output = $commandTester->getDisplay();
        $words = explode(' ', $output);

        foreach ($words as $word) {
            $result = substr($word, 1);
            if ($result = 'm') {
                $file = trim($word);
            }
        }

        $this->assertStringContainsString('Create new migration y/n:', $output);

        $expectedPhp = <<<EOF
<?php

declare(strict_types=1);

use Yiisoft\Migration\Migration;

/**
 * Handles the dropping of table `post`.
 */
class $file extends Migration
{
    public function safeUp()
    {
        \$this->dropTable('post');
    }

    public function safeDown()
    {
        \$this->createTable('post', [
            'id' => \$this->primaryKey(),
            'title' => \$this->string(12)->notNull()->unique(),
            'body' => \$this->text(),
        ]);
    }
}

EOF;
        $generated = file_get_contents($this->aliases->get('@migration/' . $file . '.php'));
        $this->assertEqualsWithoutLE($generated, $expectedPhp);
    }

    public function testExecuteCommandAddColumnWithFields(): void
    {
        $command = $this->application->find('generate/create');

        $commandTester = new CommandTester($command);

        $commandTester->setInputs(['yes']);
        $commandTester->execute([
            'name' => 'post',
            '--command' => 'addColumn',
            '--fields' => 'position:integer'
        ]);
        $output = $commandTester->getDisplay();
        $words = explode(' ', $output);

        foreach ($words as $word) {
            $result = substr($word, 1);
            if ($result = 'm') {
                $file = trim($word);
            }
        }

        $this->assertStringContainsString('Create new migration y/n:', $output);

        $expectedPhp = <<<EOF
<?php

declare(strict_types=1);

use Yiisoft\Migration\Migration;

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

    public function testExecuteCommandDropColumnWithFields(): void
    {
        $command = $this->application->find('generate/create');

        $commandTester = new CommandTester($command);

        $commandTester->setInputs(['yes']);
        $commandTester->execute([
            'name' => 'post',
            '--command' => 'dropColumn',
            '--fields' => 'position:integer'
        ]);
        $output = $commandTester->getDisplay();
        $words = explode(' ', $output);

        foreach ($words as $word) {
            $result = substr($word, 1);
            if ($result = 'm') {
                $file = trim($word);
            }
        }

        $this->assertStringContainsString('Create new migration y/n:', $output);

        $expectedPhp = <<<EOF
<?php

declare(strict_types=1);

use Yiisoft\Migration\Migration;

/**
 * Handles dropping columns from table `post`.
 */
class $file extends Migration
{
    public function safeUp()
    {
        \$this->dropColumn('post', 'position');
    }

    public function safeDown()
    {
        \$this->addColumn('post', 'position', \$this->integer());
    }
}

EOF;
        $generated = file_get_contents($this->aliases->get('@migration/' . $file . '.php'));
        $this->assertEqualsWithoutLE($generated, $expectedPhp);
    }

    public function testExecuteCommandJuntionWithFields(): void
    {
        $command = $this->application->find('generate/create');

        $commandTester = new CommandTester($command);

        $commandTester->setInputs(['yes']);
        $commandTester->execute([
            'name' => 'post',
            '--command' => 'junction',
            '--and' => 'tag',
            '--fields' => 'created_at:dateTime'
        ]);
        $output = $commandTester->getDisplay();
        $words = explode(' ', $output);

        foreach ($words as $word) {
            $result = substr($word, 1);
            if ($result = 'm') {
                $file = trim($word);
            }
        }

        $this->assertStringContainsString('Create new migration y/n:', $output);

        $expectedPhp = <<<EOF
<?php

declare(strict_types=1);

use Yiisoft\Migration\Migration;

/**
 * Handles the creation of table `post_tag`.
 * Has foreign keys to the tables:
 *
 * - `{{%post}}`
 * - `{{%tag}}`
 */
class $file extends Migration
{
    public function safeUp()
    {
        \$this->createTable('post_tag', [
            'post_id' => \$this->integer(),
            'tag_id' => \$this->integer(),
            'created_at' => \$this->dateTime(),
            'PRIMARY KEY(post_id, tag_id)',
        ]);

        // creates index for column `post_id`
        \$this->createIndex(
            '{{%idx-post_tag-post_id}}',
            'post_tag',
            'post_id'
        );

        // add foreign key for table `{{%post}}`
        \$this->addForeignKey(
            '{{%fk-post_tag-post_id}}',
            'post_tag',
            'post_id',
            '{{%post}}',
            'id',
            'CASCADE'
        );

        // creates index for column `tag_id`
        \$this->createIndex(
            '{{%idx-post_tag-tag_id}}',
            'post_tag',
            'tag_id'
        );

        // add foreign key for table `{{%tag}}`
        \$this->addForeignKey(
            '{{%fk-post_tag-tag_id}}',
            'post_tag',
            'tag_id',
            '{{%tag}}',
            'id',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        // drops foreign key for table `{{%post}}`
        \$this->dropForeignKey(
            '{{%fk-post_tag-post_id}}',
            'post_tag'
        );

        // drops index for column `post_id`
        \$this->dropIndex(
            '{{%idx-post_tag-post_id}}',
            'post_tag'
        );

        // drops foreign key for table `{{%tag}}`
        \$this->dropForeignKey(
            '{{%fk-post_tag-tag_id}}',
            'post_tag'
        );

        // drops index for column `tag_id`
        \$this->dropIndex(
            '{{%idx-post_tag-tag_id}}',
            'post_tag'
        );

        \$this->dropTable('post_tag');
    }
}

EOF;
        $generated = file_get_contents($this->aliases->get('@migration/' . $file . '.php'));
        $this->assertEqualsWithoutLE($generated, $expectedPhp);
    }
}
