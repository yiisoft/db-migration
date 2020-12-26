<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Command\Paths;

use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Yii\Console\ExitCode;

final class CreateCommandTest extends PathsCommandTest
{
    public function testExecute(): void
    {
        $command = $this->getCommand();

        $command->setInputs(['yes']);

        $this->assertEquals(
            ExitCode::OK,
            $command->execute([
                'name' => 'post',
            ])
        );

        $output = $command->getDisplay(true);
        $className = $this->findMigrationClassNameInOutput($output);

        $this->assertStringContainsString('Create new migration y/n:', $output);

        $expectedPhp = <<<EOF
<?php

declare(strict_types=1);

use Yiisoft\Yii\Db\Migration\Migration;
use Yiisoft\Yii\Db\Migration\RevertibleMigrationInterface;

/**
 * Class $className
 */
final class $className extends Migration implements RevertibleMigrationInterface
{
    public function up(): void
    {

    }

    public function down(): void
    {

    }
}

EOF;
        $generated = file_get_contents($this->getPath() . '/' . $className . '.php');
        $this->assertEqualsWithoutLE($generated, $expectedPhp);
    }

    public function testExecuteNameException(): void
    {
        $command = $this->getCommand();

        $command->setInputs(['yes']);

        $this->assertEquals(
            ExitCode::DATAERR,
            $command->execute([
                'name' => 'post?',
            ])
        );
    }

    public function testExecuteCommandException(): void
    {
        $command = $this->getCommand();

        $command->setInputs(['yes']);

        $this->assertEquals(
            ExitCode::DATAERR,
            $command->execute([
                'name' => 'post',
                '--command' => 'noExist',
            ])
        );
    }

    public function testExecuteNameToLongException(): void
    {
        $command = $this->getCommand();

        $this->assertEquals(
            ExitCode::DATAERR,
            $command->execute([
                'name' => str_repeat('x', 200),
            ])
        );
    }

    public function testAddColumn(): void
    {
        $command = $this->getCommand();

        $command->setInputs(['yes']);

        $this->assertEquals(
            ExitCode::OK,
            $command->execute([
                'name' => 'post',
                '--command' => 'addColumn',
                '--fields' => 'position:integer',
            ])
        );

        $output = $command->getDisplay(true);
        $className = $this->findMigrationClassNameInOutput($output);

        $this->assertStringContainsString('Create new migration y/n:', $output);

        $expectedPhp = <<<EOF
<?php

declare(strict_types=1);

use Yiisoft\Yii\Db\Migration\Migration;
use Yiisoft\Yii\Db\Migration\RevertibleMigrationInterface;

/**
 * Handles adding columns to table `post`.
 */
final class $className extends Migration implements RevertibleMigrationInterface
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
        $generated = file_get_contents($this->getPath() . '/' . $className . '.php');
        $this->assertEqualsWithoutLE($generated, $expectedPhp);
    }

    public function testDropColumn(): void
    {
        $command = $this->getCommand();

        $command->setInputs(['yes']);

        $this->assertEquals(
            ExitCode::OK,
            $command->execute([
                'name' => 'post',
                '--command' => 'dropColumn',
                '--fields' => 'position:integer',
            ])
        );

        $output = $command->getDisplay(true);
        $className = $this->findMigrationClassNameInOutput($output);

        $this->assertStringContainsString('Create new migration y/n:', $output);

        $expectedPhp = <<<EOF
<?php

declare(strict_types=1);

use Yiisoft\Yii\Db\Migration\Migration;
use Yiisoft\Yii\Db\Migration\RevertibleMigrationInterface;

/**
 * Handles dropping columns from table `post`.
 */
final class $className extends Migration implements RevertibleMigrationInterface
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
        $generated = file_get_contents($this->getPath() . '/' . $className . '.php');
        $this->assertEqualsWithoutLE($generated, $expectedPhp);
    }

    public function testDropTable(): void
    {
        $command = $this->getCommand();

        $command->setInputs(['yes']);

        $this->assertEquals(
            ExitCode::OK,
            $command->execute([
                'name' => 'post',
                '--command' => 'dropTable',
                '--fields' => 'title:string(12):notNull:unique,body:text',
            ])
        );

        $output = $command->getDisplay(true);
        $className = $this->findMigrationClassNameInOutput($output);

        $this->assertStringContainsString('Create new migration y/n:', $output);

        $expectedPhp = <<<EOF
<?php

declare(strict_types=1);

use Yiisoft\Yii\Db\Migration\Migration;
use Yiisoft\Yii\Db\Migration\RevertibleMigrationInterface;

/**
 * Handles the dropping of table `post`.
 */
final class $className extends Migration implements RevertibleMigrationInterface
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
        $generated = file_get_contents($this->getPath() . '/' . $className . '.php');
        $this->assertEqualsWithoutLE($generated, $expectedPhp);
    }

    public function testJunction(): void
    {
        $command = $this->getCommand();

        $command->setInputs(['yes']);

        $this->assertEquals(
            ExitCode::OK,
            $command->execute([
                'name' => 'post',
                '--command' => 'junction',
                '--and' => 'tag',
                '--fields' => 'created_at:dateTime',
            ])
        );

        $output = $command->getDisplay(true);
        $className = $this->findMigrationClassNameInOutput($output);

        $this->assertStringContainsString('Create new migration y/n:', $output);

        $expectedPhp = <<<EOF
<?php

declare(strict_types=1);

use Yiisoft\Yii\Db\Migration\Migration;
use Yiisoft\Yii\Db\Migration\RevertibleMigrationInterface;

/**
 * Handles the creation of table `post_tag`.
 * Has foreign keys to the tables:
 *
 * - `{{%post}}`
 * - `{{%tag}}`
 */
final class $className extends Migration implements RevertibleMigrationInterface
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
        $generated = file_get_contents($this->getPath() . '/' . $className . '.php');
        $this->assertEqualsWithoutLE($generated, $expectedPhp);
    }

    public function testCreateTable(): void
    {
        $command = $this->getCommand();

        $command->setInputs(['yes']);

        $this->assertEquals(
            ExitCode::OK,
            $command->execute([
                'name' => 'post',
                '--command' => 'table',
            ])
        );

        $output = $command->getDisplay(true);
        $className = $this->findMigrationClassNameInOutput($output);

        $this->assertStringContainsString('Create new migration y/n:', $output);

        $expectedPhp = <<<EOF
<?php

declare(strict_types=1);

use Yiisoft\Yii\Db\Migration\Migration;
use Yiisoft\Yii\Db\Migration\RevertibleMigrationInterface;

/**
 * Handles the creation of table `post`.
 */
final class $className extends Migration implements RevertibleMigrationInterface
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
        $generated = file_get_contents($this->getPath() . '/' . $className . '.php');
        $this->assertEqualsWithoutLE($generated, $expectedPhp);
    }

    public function testCreateTableWithFields(): void
    {
        $command = $this->getCommand();

        $command->setInputs(['yes']);

        $this->assertEquals(
            ExitCode::OK,
            $command->execute([
                'name' => 'post',
                '--command' => 'table',
                '--fields' => 'title:string,body:text',
            ])
        );

        $output = $command->getDisplay(true);
        $className = $this->findMigrationClassNameInOutput($output);

        $this->assertStringContainsString('Create new migration y/n:', $output);

        $expectedPhp = <<<EOF
<?php

declare(strict_types=1);

use Yiisoft\Yii\Db\Migration\Migration;
use Yiisoft\Yii\Db\Migration\RevertibleMigrationInterface;

/**
 * Handles the creation of table `post`.
 */
final class $className extends Migration implements RevertibleMigrationInterface
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
        $generated = file_get_contents($this->getPath() . '/' . $className . '.php');
        $this->assertEqualsWithoutLE($generated, $expectedPhp);
    }

    public function testCreateTableWithFieldsForeignKey(): void
    {
        $command = $this->getCommand();

        $command->setInputs(['yes']);

        $this->assertEquals(
            ExitCode::OK,
            $command->execute([
                'name' => 'post',
                '--command' => 'table',
                '--fields' => 'author_id:integer:notNull:foreignKey(user),category_id:integer:defaultValue(1)' .
                    ':foreignKey,title:string,body:text',
            ])
        );

        $output = $command->getDisplay(true);
        $className = $this->findMigrationClassNameInOutput($output);

        $this->assertStringContainsString('Create new migration y/n:', $output);

        $expectedPhp = <<<EOF
<?php

declare(strict_types=1);

use Yiisoft\Yii\Db\Migration\Migration;
use Yiisoft\Yii\Db\Migration\RevertibleMigrationInterface;

/**
 * Handles the creation of table `post`.
 * Has foreign keys to the tables:
 *
 * - `{{%user}}`
 * - `{{%category}}`
 */
final class $className extends Migration implements RevertibleMigrationInterface
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
        $generated = file_get_contents($this->getPath() . '/' . $className . '.php');
        $this->assertEqualsWithoutLE($generated, $expectedPhp);
    }

    public function testIncorrectCreatePath(): void
    {
        $this->getMigrationService()->createPath(__DIR__ . '/not-exists');

        $command = $this->getCommand();

        $exitCode = $command->execute(['name' => 'post']);

        $this->assertSame(ExitCode::DATAERR, $exitCode);
    }

    public function testWithoutCreatePath(): void
    {
        $this->getMigrationService()->createPath('');

        $command = $this->getCommand();

        $exitCode = $command->execute(['name' => 'post']);

        $this->assertSame(ExitCode::DATAERR, $exitCode);
    }

    private function getCommand(): CommandTester
    {
        return new CommandTester(
            $this->getApplication()->find('generate/create')
        );
    }

    private function findMigrationClassNameInOutput(string $output): string
    {
        $words = explode(' ', $output);

        foreach ($words as $word) {
            if (preg_match('/^\s*m\d{6}/i', $word)) {
                return trim($word);
            }
        }

        return '';
    }
}
