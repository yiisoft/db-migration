<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Driver\Sqlite;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Console\Command\Command;
use Yiisoft\Db\Migration\Tests\Common\Command\AbstractCreateCommandTest;
use Yiisoft\Db\Migration\Tests\Support\Factory\SqLiteFactory;
use Yiisoft\Db\Migration\Tests\Support\Helper\ContainerConfig;
use Yiisoft\Db\Migration\Tests\Support\Helper\MigrationHelper;

use function file_get_contents;
use function glob;
use function str_contains;

/**
 * @group sqlite
 */
final class CreateCommandTest extends AbstractCreateCommandTest
{
    public function setUp(): void
    {
        parent::setUp();
        $this->container = SqLiteFactory::createContainer();
        $this->driverName = 'sqlite';
    }

    public function tearDown(): void
    {
        parent::tearDown();
        SqLiteFactory::clearDatabase($this->container);
    }

    public static function transactionModes(): array
    {
        return [
            'blank default' => ['create', null, [], false],
            'table default' => ['table', null, [], true],
            'configured on' => ['create', true, [], true],
            'configured off' => ['table', false, [], false],
            'override on' => ['create', null, ['--transaction' => true], true],
            'override off' => ['table', null, ['--no-transaction' => true], false],
            'override configured on' => ['create', true, ['--no-transaction' => true], false],
            'override configured off' => ['table', false, ['--transaction' => true], true],
        ];
    }

    #[DataProvider('transactionModes')]
    public function testTransactionMode(string $type, ?bool $default, array $options, bool $expected): void
    {
        $config = new ContainerConfig();
        $config->newMigrationTransactional = $default;
        $this->container = SqLiteFactory::createContainer($config);
        $path = MigrationHelper::useMigrationsPath($this->container);
        $command = $this->createCommand($this->container);

        $exitCode = $command->execute(['name' => 'post', '--command' => $type] + $options);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $files = glob($path . '/*.php');
        $this->assertCount(1, $files);
        $this->assertSame($expected, str_contains(file_get_contents($files[0]), 'TransactionalMigrationInterface'));
    }

    public function testTransactionOverrideDoesNotAffectLaterRuns(): void
    {
        $path = MigrationHelper::useMigrationsPath($this->container);
        $command = $this->createCommand($this->container);

        $firstExitCode = $command->execute(['name' => 'First', '--transaction' => true]);
        $secondExitCode = $command->execute(['name' => 'Second']);

        $this->assertSame(Command::SUCCESS, $firstExitCode);
        $this->assertSame(Command::SUCCESS, $secondExitCode);
        $this->assertStringContainsString(
            'TransactionalMigrationInterface',
            file_get_contents(glob($path . '/*First.php')[0]),
        );
        $this->assertStringNotContainsString(
            'TransactionalMigrationInterface',
            file_get_contents(glob($path . '/*Second.php')[0]),
        );
    }

    public function testWithoutTablePrefix(): void
    {
        $containerConfig = new ContainerConfig();
        $containerConfig->useTablePrefix = false;

        $this->container = SqLiteFactory::createContainer($containerConfig);

        parent::testWithoutTablePrefix();
    }
}
