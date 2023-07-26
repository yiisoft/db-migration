<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Common\Command;

use FTP\Connection;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Db\Migration\Command\CreateCommand;
use Yiisoft\Yii\Db\Migration\Service\MigrationService;
use Yiisoft\Yii\Db\Migration\Tests\Support\AssertTrait;
use Yiisoft\Yii\Db\Migration\Tests\Support\Factory\SqLiteFactory;
use Yiisoft\Yii\Db\Migration\Tests\Support\Helper\CommandHelper;
use Yiisoft\Yii\Db\Migration\Tests\Support\Helper\ContainerConfig;
use Yiisoft\Yii\Db\Migration\Tests\Support\Helper\DbHelper;
use Yiisoft\Yii\Db\Migration\Tests\Support\Helper\MigrationHelper;

abstract class AbstractCreateCommandTest extends TestCase
{
    use AssertTrait;

    protected ContainerInterface $container;

    public function testCreateTableWithPath(): void
    {
        $migrationsPath = MigrationHelper::useMigrationsPath($this->container);

        $command = $this->createCommand($this->container);
        $command->setInputs(['yes']);
        $exitCode = $command->execute(['name' => 'post', '--command' => 'table']);
        $output = $command->getDisplay(true);

        $className = MigrationHelper::findMigrationClassNameInOutput($output);

        $expectedMigrationCode = <<<EOF
<?php

declare(strict_types=1);

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
        $generatedMigrationCode = file_get_contents($migrationsPath . '/' . $className . '.php');

        $this->assertSame(ExitCode::OK, $exitCode);
        $this->assertStringContainsString('Create new migration y/n:', $output);
        $this->assertEqualsWithoutLE($expectedMigrationCode, $generatedMigrationCode);
    }

    public function testCreateTableWithNamespace(): void
    {
        $migrationsPath = MigrationHelper::useMigrationsNamespace($this->container);

        $command = $this->createCommand($this->container);
        $command->setInputs(['yes']);
        $exitCode = $command->execute(['name' => 'post', '--command' => 'table']);
        $output = $command->getDisplay(true);

        $className = MigrationHelper::findMigrationClassNameInOutput($output);
        $namespace = MigrationHelper::NAMESPACE;

        $expectedMigrationCode = <<<EOF
<?php

declare(strict_types=1);

namespace $namespace;

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
        $generatedMigrationCode = file_get_contents($migrationsPath . '/' . $className . '.php');

        $this->assertSame(ExitCode::OK, $exitCode);
        $this->assertStringContainsString('Create new migration y/n:', $output);
        $this->assertEqualsWithoutLE($expectedMigrationCode, $generatedMigrationCode);
    }

    public function testCreateTableExtends(): void
    {
        $db = $this->container->get(ConnectionInterface::class);

        $migrationsPath = MigrationHelper::useMigrationsNamespace($this->container);

        DbHelper::createTable($db, 'user', ['[[id]]' => 'int primary key']);
        DbHelper::createTable($db, 'tag', ['[[id]]' => 'int']);
        DbHelper::createTable(
            $db,
            'category',
            ['[[id1]]' => 'int', '[[id2]]' => 'int', 'primary key ([[id1]], [[id2]])'],
        );

        $command = $this->createCommand($this->container);
        $command->setInputs(['yes']);

        $exitCode = $command->execute([
            'name' => 'post',
            '--command' => 'table',
            '--table-comment' => "test 'comment'",
            '--fields' => 'uid:primaryKey,' .
                'name:string:defaultValue("test:name"),' .
                'user_id:integer:foreignKey,' .
                'tag_id:integer:foreignKey,' .
                'category_id:integer:foreignKey,' .
                'category_id2:integer:foreignKey(category id2)',
        ]);
        $output = $command->getDisplay(true);

        $className = MigrationHelper::findMigrationClassNameInOutput($output);
        $namespace = MigrationHelper::NAMESPACE;

        $expectedMigrationCode = <<<EOF
<?php

declare(strict_types=1);

namespace $namespace;

use Yiisoft\Yii\Db\Migration\MigrationBuilder;
use Yiisoft\Yii\Db\Migration\RevertibleMigrationInterface;

/**
 * Handles the creation of table `post`.
 * Has foreign keys to the tables:
 *
 * - `{{%user}}`
 * - `{{%tag}}`
 * - `{{%category}}`
 * - `{{%category}}`
 */
final class $className implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder \$b): void
    {
        \$b->createTable('post', [
            'uid' => \$b->primaryKey(),
            'name' => \$b->string()->defaultValue("test:name"),
            'user_id' => \$b->integer(),
            'tag_id' => \$b->integer(),
            'category_id' => \$b->integer(),
            'category_id2' => \$b->integer(),
        ]);

        // creates index for column `user_id`
        \$b->createIndex(
            'post',
            'idx-post-user_id',
            'user_id'
        );

        // add foreign key for table `{{%user}}`
        \$b->addForeignKey(
            'post',
            'fk-post-user_id',
            'user_id',
            '{{%user}}',
            'id',
            'CASCADE'
        );

        // creates index for column `tag_id`
        \$b->createIndex(
            'post',
            'idx-post-tag_id',
            'tag_id'
        );

        // add foreign key for table `{{%tag}}`
        \$b->addForeignKey(
            'post',
            'fk-post-tag_id',
            'tag_id',
            '{{%tag}}',
            'id',
            'CASCADE'
        );

        // creates index for column `category_id`
        \$b->createIndex(
            'post',
            'idx-post-category_id',
            'category_id'
        );

        // add foreign key for table `{{%category}}`
        \$b->addForeignKey(
            'post',
            'fk-post-category_id',
            'category_id',
            '{{%category}}',
            'id',
            'CASCADE'
        );

        // creates index for column `category_id2`
        \$b->createIndex(
            'post',
            'idx-post-category_id2',
            'category_id2'
        );

        // add foreign key for table `{{%category}}`
        \$b->addForeignKey(
            'post',
            'fk-post-category_id2',
            'category_id2',
            '{{%category}}',
            'id2',
            'CASCADE'
        );

        \$b->addCommentOnTable('post', 'test \'comment\'');
    }

    public function down(MigrationBuilder \$b): void
    {
        // drops foreign key for table `{{%user}}`
        \$b->dropForeignKey(
            'post',
            'fk-post-user_id'
        );

        // drops index for column `user_id`
        \$b->dropIndex(
            'post',
            'idx-post-user_id'
        );

        // drops foreign key for table `{{%tag}}`
        \$b->dropForeignKey(
            'post',
            'fk-post-tag_id'
        );

        // drops index for column `tag_id`
        \$b->dropIndex(
            'post',
            'idx-post-tag_id'
        );

        // drops foreign key for table `{{%category}}`
        \$b->dropForeignKey(
            'post',
            'fk-post-category_id'
        );

        // drops index for column `category_id`
        \$b->dropIndex(
            'post',
            'idx-post-category_id'
        );

        // drops foreign key for table `{{%category}}`
        \$b->dropForeignKey(
            'post',
            'fk-post-category_id2'
        );

        // drops index for column `category_id2`
        \$b->dropIndex(
            'post',
            'idx-post-category_id2'
        );

        \$b->dropTable('post');
    }
}

EOF;
        $generatedMigrationCode = file_get_contents($migrationsPath . '/' . $className . '.php');

        $this->assertSame(ExitCode::OK, $exitCode);
        $this->assertStringContainsString('Create new migration y/n:', $output);
        $this->assertEqualsWithoutLE($expectedMigrationCode, $generatedMigrationCode);
    }

    public function testWithoutTablePrefix(): void
    {
        $migrationsPath = MigrationHelper::useMigrationsNamespace($this->container);

        $command = $this->createCommand($this->container);
        $command->setInputs(['yes']);

        $command->execute(
            [
                'name' => 'post',
                '--command' => 'table',
                '--fields' => 'name:string,user_id:integer:foreignKey',
            ]
        );
        $output = $command->getDisplay(true);

        $className = MigrationHelper::findMigrationClassNameInOutput($output);
        $namespace = MigrationHelper::NAMESPACE;

        $expectedMigrationCode = <<<EOF
<?php

declare(strict_types=1);

namespace $namespace;

use Yiisoft\Yii\Db\Migration\MigrationBuilder;
use Yiisoft\Yii\Db\Migration\RevertibleMigrationInterface;

/**
 * Handles the creation of table `post`.
 * Has foreign keys to the tables:
 *
 * - `user`
 */
final class $className implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder \$b): void
    {
        \$b->createTable('post', [
            'id' => \$b->primaryKey(),
            'name' => \$b->string(),
            'user_id' => \$b->integer(),
        ]);

        // creates index for column `user_id`
        \$b->createIndex(
            'post',
            'idx-post-user_id',
            'user_id'
        );

        // add foreign key for table `user`
        \$b->addForeignKey(
            'post',
            'fk-post-user_id',
            'user_id',
            'user',
            'id',
            'CASCADE'
        );
    }

    public function down(MigrationBuilder \$b): void
    {
        // drops foreign key for table `user`
        \$b->dropForeignKey(
            'post',
            'fk-post-user_id'
        );

        // drops index for column `user_id`
        \$b->dropIndex(
            'post',
            'idx-post-user_id'
        );

        \$b->dropTable('post');
    }
}

EOF;
        $generatedMigrationCode = file_get_contents($migrationsPath . '/' . $className . '.php');

        $this->assertEqualsWithoutLE($expectedMigrationCode, $generatedMigrationCode);
    }

    public function testExecuteInputNamespaces(): void
    {
        MigrationHelper::useMigrationsPath($this->container);

        $command = $this->createCommand($this->container);
        $command->setInputs(['yes']);

        $exitCode = $command->execute(['name' => 'post', '--namespace' => MigrationHelper::NAMESPACE]);
        $output = $command->getDisplay(true);

        $className = MigrationHelper::findMigrationClassNameInOutput($output);
        $namespace = MigrationHelper::NAMESPACE;

        $expectedMigrationCode = <<<EOF
<?php

declare(strict_types=1);

namespace $namespace;

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
        $generatedMigrationCode = file_get_contents(MigrationHelper::getPathForMigrationNamespace() . '/' . $className . '.php');

        $this->assertSame(ExitCode::OK, $exitCode);
        $this->assertStringContainsString('Create new migration y/n:', $output);
        $this->assertEqualsWithoutLE($expectedMigrationCode, $generatedMigrationCode);
    }

    public function testExecuteNameException(): void
    {
        MigrationHelper::useMigrationsNamespace($this->container);

        $command = $this->createCommand($this->container);
        $command->setInputs(['yes']);

        $exitCode = $command->execute(['name' => 'post?']);
        $output = $command->getDisplay(true);

        $this->assertSame(ExitCode::DATAERR, $exitCode);
        $this->assertStringContainsStringCollapsingSpaces(
            'The migration name should contain letters, digits, underscore and/or backslash characters only.',
            $output
        );
    }

    public function testExecuteCommandException(): void
    {
        MigrationHelper::useMigrationsNamespace($this->container);

        $command = $this->createCommand($this->container);
        $command->setInputs(['yes']);

        $exitCode = $command->execute(['name' => 'post', '--command' => 'noExist']);
        $output = $command->getDisplay(true);

        $this->assertSame(ExitCode::DATAERR, $exitCode);
        $this->assertStringContainsStringCollapsingSpaces(
            'Command not found "noExist". Available commands: ' .
            'create, table, dropTable, addColumn, dropColumn, junction.',
            $output
        );
    }

    public function testExecuteNameToLongException(): void
    {
        MigrationHelper::useMigrationsNamespace($this->container);

        $command = $this->createCommand($this->container);
        $command->setInputs(['yes']);

        $exitCode = $command->execute([
            'name' => str_repeat('x', 200),
        ]);
        $output = $command->getDisplay(true);

        $this->assertSame(ExitCode::DATAERR, $exitCode);
        $this->assertStringContainsString('The migration name is too long.', $output);
    }

    public function testAddColumn(): void
    {
        $migrationsPath = MigrationHelper::useMigrationsNamespace($this->container);

        $command = $this->createCommand($this->container);
        $command->setInputs(['yes']);

        $exitCode = $command->execute(['name' => 'post', '--command' => 'addColumn', '--fields' => 'position:integer']);
        $output = $command->getDisplay(true);

        $className = MigrationHelper::findMigrationClassNameInOutput($output);
        $namespace = MigrationHelper::NAMESPACE;

        $expectedMigrationCode = <<<EOF
<?php

declare(strict_types=1);

namespace $namespace;

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
        $generatedMigrationCode = file_get_contents($migrationsPath . '/' . $className . '.php');

        $this->assertSame(ExitCode::OK, $exitCode);
        $this->assertStringContainsString('Create new migration y/n:', $output);
        $this->assertEqualsWithoutLE($expectedMigrationCode, $generatedMigrationCode);
    }

    public function testDropColumn(): void
    {
        $migrationsPath = MigrationHelper::useMigrationsNamespace($this->container);

        $command = $this->createCommand($this->container);
        $command->setInputs(['yes']);

        $exitCode = $command->execute([
            'name' => 'post',
            '--command' => 'dropColumn',
            '--fields' => 'position:integer',
        ]);
        $output = $command->getDisplay(true);

        $className = MigrationHelper::findMigrationClassNameInOutput($output);
        $namespace = MigrationHelper::NAMESPACE;

        $expectedMigrationCode = <<<EOF
<?php

declare(strict_types=1);

namespace $namespace;

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
        $generatedMigrationCode = file_get_contents($migrationsPath . '/' . $className . '.php');

        $this->assertSame(ExitCode::OK, $exitCode);
        $this->assertStringContainsString('Create new migration y/n:', $output);
        $this->assertEqualsWithoutLE($expectedMigrationCode, $generatedMigrationCode);
    }

    public function testDropTable(): void
    {
        $migrationsPath = MigrationHelper::useMigrationsNamespace($this->container);

        $command = $this->createCommand($this->container);
        $command->setInputs(['yes']);

        $exitCode = $command->execute(
            [
                'name' => 'post',
                '--command' => 'dropTable',
                '--fields' => 'title:string(12):notNull:unique,body:text',
            ]
        );
        $output = $command->getDisplay(true);

        $className = MigrationHelper::findMigrationClassNameInOutput($output);
        $namespace = MigrationHelper::NAMESPACE;

        $expectedMigrationCode = <<<EOF
<?php

declare(strict_types=1);

namespace $namespace;

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
        $generatedMigrationCode = file_get_contents($migrationsPath . '/' . $className . '.php');

        $this->assertSame(ExitCode::OK, $exitCode);
        $this->assertStringContainsString('Create new migration y/n:', $output);
        $this->assertEqualsWithoutLE($expectedMigrationCode, $generatedMigrationCode);
    }

    public function testCreateTableWithFields(): void
    {
        $migrationsPath = MigrationHelper::useMigrationsNamespace($this->container);

        $command = $this->createCommand($this->container);
        $command->setInputs(['yes']);

        $exitCode = $command->execute(
            [
                'name' => 'post',
                '--command' => 'table',
                '--fields' => 'title:string,body:text',
            ]
        );
        $output = $command->getDisplay(true);

        $className = MigrationHelper::findMigrationClassNameInOutput($output);
        $namespace = MigrationHelper::NAMESPACE;

        $expectedMigrationCode = <<<EOF
<?php

declare(strict_types=1);

namespace $namespace;

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
        $generatedMigrationCode = file_get_contents($migrationsPath . '/' . $className . '.php');

        $this->assertSame(ExitCode::OK, $exitCode);
        $this->assertStringContainsString('Create new migration y/n:', $output);
        $this->assertEqualsWithoutLE($expectedMigrationCode, $generatedMigrationCode);
    }

    public function testCreateTableWithFieldsForeignKey(): void
    {
        $migrationsPath = MigrationHelper::useMigrationsNamespace($this->container);

        $command = $this->createCommand($this->container);
        $command->setInputs(['yes']);

        $exitCode = $command->execute(
            [
                'name' => 'post',
                '--command' => 'table',
                '--fields' => 'author_id:integer:notNull:foreignKey(user),' .
                    'category_id:integer:defaultValue(1):foreignKey,title:string,body:text',
            ]
        );
        $output = $command->getDisplay(true);

        $className = MigrationHelper::findMigrationClassNameInOutput($output);
        $namespace = MigrationHelper::NAMESPACE;

        $expectedMigrationCode = <<<EOF
<?php

declare(strict_types=1);

namespace $namespace;

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
            'post',
            'idx-post-author_id',
            'author_id'
        );

        // add foreign key for table `{{%user}}`
        \$b->addForeignKey(
            'post',
            'fk-post-author_id',
            'author_id',
            '{{%user}}',
            'id',
            'CASCADE'
        );

        // creates index for column `category_id`
        \$b->createIndex(
            'post',
            'idx-post-category_id',
            'category_id'
        );

        // add foreign key for table `{{%category}}`
        \$b->addForeignKey(
            'post',
            'fk-post-category_id',
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
            'post',
            'fk-post-author_id'
        );

        // drops index for column `author_id`
        \$b->dropIndex(
            'post',
            'idx-post-author_id'
        );

        // drops foreign key for table `{{%category}}`
        \$b->dropForeignKey(
            'post',
            'fk-post-category_id'
        );

        // drops index for column `category_id`
        \$b->dropIndex(
            'post',
            'idx-post-category_id'
        );

        \$b->dropTable('post');
    }
}

EOF;
        $generatedMigrationCode = file_get_contents($migrationsPath . '/' . $className . '.php');

        $this->assertSame(ExitCode::OK, $exitCode);
        $this->assertStringContainsString('Create new migration y/n:', $output);
        $this->assertEqualsWithoutLE($expectedMigrationCode, $generatedMigrationCode);
    }

    public function testJunction(): void
    {
        $migrationsPath = MigrationHelper::useMigrationsNamespace($this->container);

        $command = $this->createCommand($this->container);
        $command->setInputs(['yes']);

        $exitCode = $command->execute(
            [
                'name' => 'post',
                '--command' => 'junction',
                '--and' => 'tag',
                '--fields' => 'created_at:dateTime',
            ]
        );
        $output = $command->getDisplay(true);

        $className = MigrationHelper::findMigrationClassNameInOutput($output);
        $namespace = MigrationHelper::NAMESPACE;

        $expectedMigrationCode = <<<EOF
<?php

declare(strict_types=1);

namespace $namespace;

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
            'post_tag',
            'idx-post_tag-post_id',
            'post_id'
        );

        // add foreign key for table `{{%post}}`
        \$b->addForeignKey(
            'post_tag',
            'fk-post_tag-post_id',
            'post_id',
            '{{%post}}',
            'id',
            'CASCADE'
        );

        // creates index for column `tag_id`
        \$b->createIndex(
            'post_tag',
            'idx-post_tag-tag_id',
            'tag_id'
        );

        // add foreign key for table `{{%tag}}`
        \$b->addForeignKey(
            'post_tag',
            'fk-post_tag-tag_id',
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
            'post_tag',
            'fk-post_tag-post_id'
        );

        // drops index for column `post_id`
        \$b->dropIndex(
            'post_tag',
            'idx-post_tag-post_id'
        );

        // drops foreign key for table `{{%tag}}`
        \$b->dropForeignKey(
            'post_tag',
            'fk-post_tag-tag_id'
        );

        // drops index for column `tag_id`
        \$b->dropIndex(
            'post_tag',
            'idx-post_tag-tag_id'
        );

        \$b->dropTable('post_tag');
    }
}

EOF;
        $generatedMigrationCode = file_get_contents($migrationsPath . '/' . $className . '.php');

        $this->assertSame(ExitCode::OK, $exitCode);
        $this->assertStringContainsString('Create new migration y/n:', $output);
        $this->assertEqualsWithoutLE($expectedMigrationCode, $generatedMigrationCode);
    }

    public function testIncorrectCreatePath(): void
    {
        MigrationHelper::useMigrationsPath($this->container);

        $this->container->get(MigrationService::class)->createPath(__DIR__ . '/not-exists');

        $command = $this->createCommand($this->container);
        $command->setInputs(['yes']);

        $exitCode = $command->execute(['name' => 'post']);
        $output = $command->getDisplay(true);

        $this->assertSame(ExitCode::DATAERR, $exitCode);
        $this->assertStringContainsString('Invalid path directory', $output);
    }

    public function testWithoutCreatePath(): void
    {
        MigrationHelper::useMigrationsPath($this->container);

        $this->container->get(MigrationService::class)->createPath('');

        $command = $this->createCommand($this->container);
        $command->setInputs(['yes']);

        $exitCode = $command->execute(['name' => 'post']);
        $output = $command->getDisplay(true);

        $this->assertSame(ExitCode::DATAERR, $exitCode);
        $this->assertStringContainsString(
            'At least one of `createNamespace` or `createPath` should be specified.',
            $output
        );
    }

    public function testIncorrectCreateNamespace(): void
    {
        MigrationHelper::useMigrationsNamespace($this->container);

        $this->container->get(MigrationService::class)
            ->createNamespace('Yiisoft\\Yii\Db\\Migration\\TestsRuntime\\NotExists');

        $command = $this->createCommand($this->container);
        $command->setInputs(['yes']);

        $exitCode = $command->execute(['name' => 'post']);
        $output = $command->getDisplay(true);

        $this->assertSame(ExitCode::DATAERR, $exitCode);
        $this->assertStringContainsString('Invalid path directory', $output);
    }

    public function testWithoutCreateNamespace(): void
    {
        MigrationHelper::useMigrationsNamespace($this->container);

        $this->container->get(MigrationService::class)->createNamespace('');

        $command = $this->createCommand($this->container);
        $command->setInputs(['yes']);

        $exitCode = $command->execute(['name' => 'post']);
        $output = $command->getDisplay(true);

        $this->assertSame(ExitCode::DATAERR, $exitCode);
        $this->assertStringContainsString(
            'At least one of `createNamespace` or `createPath` should be specified.',
            $output
        );
    }

    public function createCommand(ContainerInterface $container): CommandTester
    {
        return CommandHelper::getCommandTester($container, CreateCommand::class);
    }
}
