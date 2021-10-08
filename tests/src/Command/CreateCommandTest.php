<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Command;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Db\Migration\Command\CreateCommand;
use Yiisoft\Yii\Db\Migration\Service\MigrationService;
use Yiisoft\Yii\Db\Migration\Tests\Support\AssertTrait;
use Yiisoft\Yii\Db\Migration\Tests\Support\Helper\CommandHelper;
use Yiisoft\Yii\Db\Migration\Tests\Support\Helper\DbHelper;
use Yiisoft\Yii\Db\Migration\Tests\Support\Helper\MigrationHelper;
use Yiisoft\Yii\Db\Migration\Tests\Support\Helper\SqLiteHelper;

final class CreateCommandTest extends TestCase
{
    use AssertTrait;

    public function testCreateTableWithPath(): void
    {
        $container = SqLiteHelper::createContainer();
        $migrationsPath = MigrationHelper::useMigrationsPath($container);
        SqLiteHelper::clearDatabase($container);

        $command = $this->createCommand($container);
        $command->setInputs(['yes']);

        $exitCode = $command->execute([
            'name' => 'post',
            '--command' => 'table',
        ]);
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
        $this->assertStringContainsStringIgnoringLineEndings($expectedMigrationCode, $generatedMigrationCode);
    }

    public function testCreateTableWithNamespace(): void
    {
        $container = SqLiteHelper::createContainer();
        $migrationsPath = MigrationHelper::useMigrationsNamespace($container);
        SqLiteHelper::clearDatabase($container);

        $command = $this->createCommand($container);
        $command->setInputs(['yes']);

        $exitCode = $command->execute([
            'name' => 'post',
            '--command' => 'table',
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
        $this->assertStringContainsStringIgnoringLineEndings($expectedMigrationCode, $generatedMigrationCode);
    }

    public function testCreateTableExtends(): void
    {
        $container = SqLiteHelper::createContainer();
        $migrationsPath = MigrationHelper::useMigrationsNamespace($container);

        SqLiteHelper::clearDatabase($container);
        DbHelper::createTable($container, 'user', ['id' => 'int primary key']);
        DbHelper::createTable($container, 'tag', ['id' => 'int']);
        DbHelper::createTable($container, 'category', ['id1' => 'int', 'id2' => 'int', 'primary key (id1, id2)']);

        $command = $this->createCommand($container);
        $command->setInputs(['yes']);

        $exitCode = $command->execute([
            'name' => 'post',
            '--command' => 'table',
            '--fields' => 'name:string:defaultValue("test:name"),' .
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
            'id' => \$b->primaryKey(),
            'name' => \$b->string()->defaultValue("test:name"),
            'user_id' => \$b->integer(),
            'tag_id' => \$b->integer(),
            'category_id' => \$b->integer(),
            'category_id2' => \$b->integer(),
        ]);

        // creates index for column `user_id`
        \$b->createIndex(
            'idx-post-user_id',
            'post',
            'user_id'
        );

        // add foreign key for table `{{%user}}`
        \$b->addForeignKey(
            'fk-post-user_id',
            'post',
            'user_id',
            '{{%user}}',
            'id',
            'CASCADE'
        );

        // creates index for column `tag_id`
        \$b->createIndex(
            'idx-post-tag_id',
            'post',
            'tag_id'
        );

        // add foreign key for table `{{%tag}}`
        \$b->addForeignKey(
            'fk-post-tag_id',
            'post',
            'tag_id',
            '{{%tag}}',
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

        // creates index for column `category_id2`
        \$b->createIndex(
            'idx-post-category_id2',
            'post',
            'category_id2'
        );

        // add foreign key for table `{{%category}}`
        \$b->addForeignKey(
            'fk-post-category_id2',
            'post',
            'category_id2',
            '{{%category}}',
            'id2',
            'CASCADE'
        );
    }

    public function down(MigrationBuilder \$b): void
    {
        // drops foreign key for table `{{%user}}`
        \$b->dropForeignKey(
            'fk-post-user_id',
            'post'
        );

        // drops index for column `user_id`
        \$b->dropIndex(
            'idx-post-user_id',
            'post'
        );

        // drops foreign key for table `{{%tag}}`
        \$b->dropForeignKey(
            'fk-post-tag_id',
            'post'
        );

        // drops index for column `tag_id`
        \$b->dropIndex(
            'idx-post-tag_id',
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

        // drops foreign key for table `{{%category}}`
        \$b->dropForeignKey(
            'fk-post-category_id2',
            'post'
        );

        // drops index for column `category_id2`
        \$b->dropIndex(
            'idx-post-category_id2',
            'post'
        );

        \$b->dropTable('post');
    }
}

EOF;
        $generatedMigrationCode = file_get_contents($migrationsPath . '/' . $className . '.php');

        $this->assertSame(ExitCode::OK, $exitCode);
        $this->assertStringContainsString('Create new migration y/n:', $output);
        $this->assertStringContainsStringIgnoringLineEndings($expectedMigrationCode, $generatedMigrationCode);
    }

    public function testWithoutTablePrefix(): void
    {
        $container = SqLiteHelper::createContainer();
        $migrationsPath = MigrationHelper::useMigrationsNamespace($container);
        SqLiteHelper::clearDatabase($container);

        $container->get(MigrationService::class)->useTablePrefix(false);

        $command = $this->createCommand($container);
        $command->setInputs(['yes']);

        $command->execute([
            'name' => 'post',
            '--command' => 'table',
            '--fields' => 'name:string,user_id:integer:foreignKey',
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
            'idx-post-user_id',
            'post',
            'user_id'
        );

        // add foreign key for table `user`
        \$b->addForeignKey(
            'fk-post-user_id',
            'post',
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
            'fk-post-user_id',
            'post'
        );

        // drops index for column `user_id`
        \$b->dropIndex(
            'idx-post-user_id',
            'post'
        );

        \$b->dropTable('post');
    }
}

EOF;
        $generatedMigrationCode = file_get_contents($migrationsPath . '/' . $className . '.php');

        $this->assertStringContainsStringIgnoringLineEndings($expectedMigrationCode, $generatedMigrationCode);
    }

    public function createCommand(ContainerInterface $container): CommandTester
    {
        return CommandHelper::getCommandTester($container, CreateCommand::class);
    }
}
