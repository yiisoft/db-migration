<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Command;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Db\Migration\Command\CreateCommand;
use Yiisoft\Yii\Db\Migration\Tests\Support\AssertTrait;
use Yiisoft\Yii\Db\Migration\Tests\Support\CommandHelper;
use Yiisoft\Yii\Db\Migration\Tests\Support\MigrationHelper;
use Yiisoft\Yii\Db\Migration\Tests\Support\SqlLiteHelper;

final class CreateCommandTest extends TestCase
{
    use AssertTrait;

    public function testCreateTableWithPath(): void
    {
        $container = SqlLiteHelper::createContainer();
        $migrationsPath = MigrationHelper::useMigrationsPath($container);
        SqlLiteHelper::clearDatabase($container);

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
        $container = SqlLiteHelper::createContainer();
        $migrationsPath = MigrationHelper::useMigrationsNamespace($container);
        SqlLiteHelper::clearDatabase($container);

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

    public function createCommand(ContainerInterface $container): CommandTester
    {
        return CommandHelper::getCommandTester($container, CreateCommand::class);
    }
}
