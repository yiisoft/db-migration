<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Command\Namespaces;

use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Yii\Console\ExitCode;

final class CreateCommandTest extends NamespacesCommandTest
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

namespace {$this->getNamespace()};

use Yiisoft\Yii\Db\Migration\MigrationBuilder;
use Yiisoft\Yii\Db\Migration\RevertibleMigrationInterface;

/**
 * Class $className
 */
final class $className implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder \$b): void
    {

    }

    public function down(MigrationBuilder \$b): void
    {

    }
}

EOF;
        $generated = file_get_contents(
            $this->getPath() . '/' . $className . '.php'
        );
        $this->assertEqualsWithoutLE($generated, $expectedPhp);
    }

    public function testExecuteInputNamespaces(): void
    {
        $this->getMigrationService()->createPath('@yiisoft/yii/db/migration/migration');

        $command = $this->getCommand();

        $command->setInputs(['yes']);

        $this->assertEquals(
            ExitCode::OK,
            $command->execute([
                'name' => 'post',
                '--namespace' => $this->getNamespace(),
            ])
        );

        $output = $command->getDisplay(true);
        $className = $this->findMigrationClassNameInOutput($output);

        $this->assertStringContainsString('Create new migration y/n:', $output);

        $expectedPhp = <<<EOF
<?php

declare(strict_types=1);

namespace {$this->getNamespace()};

use Yiisoft\Yii\Db\Migration\MigrationBuilder;
use Yiisoft\Yii\Db\Migration\RevertibleMigrationInterface;

/**
 * Class $className
 */
final class $className implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder \$b): void
    {

    }

    public function down(MigrationBuilder \$b): void
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

        $command->setInputs(['yes']);

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

namespace {$this->getNamespace()};

use Yiisoft\Yii\Db\Migration\MigrationBuilder;
use Yiisoft\Yii\Db\Migration\RevertibleMigrationInterface;

/**
 * Handles adding columns to table `post`.
 */
final class $className implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder \$b): void
    {
        \$b->addColumn('post', 'position', \$b->integer());
    }

    public function down(MigrationBuilder \$b): void
    {
        \$b->dropColumn('post', 'position');
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

namespace {$this->getNamespace()};

use Yiisoft\Yii\Db\Migration\MigrationBuilder;
use Yiisoft\Yii\Db\Migration\RevertibleMigrationInterface;

/**
 * Handles dropping columns from table `post`.
 */
final class $className implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder \$b): void
    {
        \$b->dropColumn('post', 'position');
    }

    public function down(MigrationBuilder \$b): void
    {
        \$b->addColumn('post', 'position', \$b->integer());
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

namespace {$this->getNamespace()};

use Yiisoft\Yii\Db\Migration\MigrationBuilder;
use Yiisoft\Yii\Db\Migration\RevertibleMigrationInterface;

/**
 * Handles the dropping of table `post`.
 */
final class $className implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder \$b): void
    {
        \$b->dropTable('post');
    }

    public function down(MigrationBuilder \$b): void
    {
        \$b->createTable('post', [
            'id' => \$b->primaryKey(),
            'title' => \$b->string(12)->notNull()->unique(),
            'body' => \$b->text(),
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

namespace {$this->getNamespace()};

use Yiisoft\Yii\Db\Migration\MigrationBuilder;
use Yiisoft\Yii\Db\Migration\RevertibleMigrationInterface;

/**
 * Handles the creation of table `post_tag`.
 * Has foreign keys to the tables:
 *
 * - `{{%post}}`
 * - `{{%tag}}`
 */
final class $className implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder \$b): void
    {
        \$b->createTable('post_tag', [
            'post_id' => \$b->integer(),
            'tag_id' => \$b->integer(),
            'created_at' => \$b->dateTime(),
            'PRIMARY KEY(post_id, tag_id)',
        ]);

        // creates index for column `post_id`
        \$b->createIndex(
            'idx-post_tag-post_id',
            'post_tag',
            'post_id'
        );

        // add foreign key for table `{{%post}}`
        \$b->addForeignKey(
            'fk-post_tag-post_id',
            'post_tag',
            'post_id',
            '{{%post}}',
            'id',
            'CASCADE'
        );

        // creates index for column `tag_id`
        \$b->createIndex(
            'idx-post_tag-tag_id',
            'post_tag',
            'tag_id'
        );

        // add foreign key for table `{{%tag}}`
        \$b->addForeignKey(
            'fk-post_tag-tag_id',
            'post_tag',
            'tag_id',
            '{{%tag}}',
            'id',
            'CASCADE'
        );
    }

    public function down(MigrationBuilder \$b): void
    {
        // drops foreign key for table `{{%post}}`
        \$b->dropForeignKey(
            'fk-post_tag-post_id',
            'post_tag'
        );

        // drops index for column `post_id`
        \$b->dropIndex(
            'idx-post_tag-post_id',
            'post_tag'
        );

        // drops foreign key for table `{{%tag}}`
        \$b->dropForeignKey(
            'fk-post_tag-tag_id',
            'post_tag'
        );

        // drops index for column `tag_id`
        \$b->dropIndex(
            'idx-post_tag-tag_id',
            'post_tag'
        );

        \$b->dropTable('post_tag');
    }
}

EOF;
        $generated = file_get_contents($this->getPath() . '/' . $className . '.php');
        $this->assertEqualsWithoutLE($generated, $expectedPhp);
    }

    public function testTable(): void
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

namespace {$this->getNamespace()};

use Yiisoft\Yii\Db\Migration\MigrationBuilder;
use Yiisoft\Yii\Db\Migration\RevertibleMigrationInterface;

/**
 * Handles the creation of table `post`.
 */
final class $className implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder \$b): void
    {
        \$b->createTable('post', [
            'id' => \$b->primaryKey(),
        ]);
    }

    public function down(MigrationBuilder \$b): void
    {
        \$b->dropTable('post');
    }
}

EOF;
        $generated = file_get_contents($this->getPath() . '/' . $className . '.php');
        $this->assertEqualsWithoutLE($generated, $expectedPhp);
    }

    public function testTableWithFields(): void
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

namespace {$this->getNamespace()};

use Yiisoft\Yii\Db\Migration\MigrationBuilder;
use Yiisoft\Yii\Db\Migration\RevertibleMigrationInterface;

/**
 * Handles the creation of table `post`.
 */
final class $className implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder \$b): void
    {
        \$b->createTable('post', [
            'id' => \$b->primaryKey(),
            'title' => \$b->string(),
            'body' => \$b->text(),
        ]);
    }

    public function down(MigrationBuilder \$b): void
    {
        \$b->dropTable('post');
    }
}

EOF;
        $generated = file_get_contents($this->getPath() . '/' . $className . '.php');
        $this->assertEqualsWithoutLE($generated, $expectedPhp);
    }

    public function testTableWithFieldsForeignKey(): void
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

namespace {$this->getNamespace()};

use Yiisoft\Yii\Db\Migration\MigrationBuilder;
use Yiisoft\Yii\Db\Migration\RevertibleMigrationInterface;

/**
 * Handles the creation of table `post`.
 * Has foreign keys to the tables:
 *
 * - `{{%user}}`
 * - `{{%category}}`
 */
final class $className implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder \$b): void
    {
        \$b->createTable('post', [
            'id' => \$b->primaryKey(),
            'author_id' => \$b->integer()->notNull(),
            'category_id' => \$b->integer()->defaultValue(1),
            'title' => \$b->string(),
            'body' => \$b->text(),
        ]);

        // creates index for column `author_id`
        \$b->createIndex(
            'idx-post-author_id',
            'post',
            'author_id'
        );

        // add foreign key for table `{{%user}}`
        \$b->addForeignKey(
            'fk-post-author_id',
            'post',
            'author_id',
            '{{%user}}',
            'id',
            'CASCADE'
        );

        // creates index for column `category_id`
        \$b->createIndex(
            'idx-post-category_id',
            'post',
            'category_id'
        );

        // add foreign key for table `{{%category}}`
        \$b->addForeignKey(
            'fk-post-category_id',
            'post',
            'category_id',
            '{{%category}}',
            'id',
            'CASCADE'
        );
    }

    public function down(MigrationBuilder \$b): void
    {
        // drops foreign key for table `{{%user}}`
        \$b->dropForeignKey(
            'fk-post-author_id',
            'post'
        );

        // drops index for column `author_id`
        \$b->dropIndex(
            'idx-post-author_id',
            'post'
        );

        // drops foreign key for table `{{%category}}`
        \$b->dropForeignKey(
            'fk-post-category_id',
            'post'
        );

        // drops index for column `category_id`
        \$b->dropIndex(
            'idx-post-category_id',
            'post'
        );

        \$b->dropTable('post');
    }
}

EOF;
        $generated = file_get_contents($this->getPath() . '/' . $className . '.php');
        $this->assertEqualsWithoutLE($generated, $expectedPhp);
    }

    public function testIncorrectCreateNamespace(): void
    {
        $this->getMigrationService()->createNamespace('Yiisoft\\Yii\Db\\Migration\\TestsRuntime\\NotExists');

        $command = $this->getCommand();

        $exitCode = $command->execute(['name' => 'post']);

        $this->assertSame(ExitCode::DATAERR, $exitCode);
    }

    public function testWithoutCreateNamespace(): void
    {
        $this->getMigrationService()->createNamespace('');

        $command = $this->getCommand();

        $exitCode = $command->execute(['name' => 'post']);

        $this->assertSame(ExitCode::DATAERR, $exitCode);
    }

    private function getCommand(): CommandTester
    {
        return new CommandTester(
            $this->getApplication()->find('migrate/create')
        );
    }
}
