<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Driver\Sqlite;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Db\Sqlite\Connection;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Migration\Command\MarkCommand;
use Yiisoft\Db\Migration\Migrator;
use Yiisoft\Db\Migration\Service\MigrationService;
use Yiisoft\Db\Migration\Tests\Common\Command\AbstractMarkCommandTest;
use Yiisoft\Db\Migration\Tests\Support\Factory\SqLiteFactory;
use Yiisoft\Db\Migration\Tests\Support\MarkMigrations\M260101000002Second;
use Yiisoft\Db\Migration\Tests\Support\MarkMigrations\M260101000003Third;

use function dirname;

/**
 * @group sqlite
 */
final class MarkCommandTest extends AbstractMarkCommandTest
{
    public function setUp(): void
    {
        parent::setUp();
        $this->container = SqLiteFactory::createContainer();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        SqLiteFactory::clearDatabase($this->container);
    }

    public static function historyOperations(): array
    {
        return [['INSERT', 'NEW'], ['DELETE', 'OLD']];
    }

    #[DataProvider('historyOperations')]
    public function testFailedHistoryChangeIsRolledBack(string $operation, string $row): void
    {
        $db = $this->container->get(Connection::class);
        $migrator = $this->container->get(Migrator::class);
        $service = $this->container->get(MigrationService::class);
        $service->setSourcePaths([dirname(__DIR__, 2) . '/Support/MarkMigrations']);
        $command = new CommandTester(new MarkCommand($service, $migrator));
        if ($operation === 'DELETE') {
            $command->execute(['version' => M260101000003Third::class, '-y' => true]);
        }
        $before = $migrator->getHistory();
        // Fail on the second change, after the first INSERT or DELETE has succeeded.
        $db->getActivePdo()->exec("CREATE TRIGGER fail_history_change BEFORE $operation ON migration
            WHEN $row.name LIKE '%Second'
            BEGIN SELECT RAISE(ABORT, 'History change failed'); END");

        try {
            $command->execute([
                'version' => $operation === 'INSERT' ? M260101000003Third::class : MarkCommand::BASE_MIGRATION,
                '-y' => true,
            ]);
            self::fail('Expected the history change to fail.');
        } catch (Exception $exception) {
            self::assertStringContainsString('History change failed', $exception->getMessage());
        }

        self::assertSame($before, $migrator->getHistory());
    }

    public function testHistoryTransactionCoversBothAdditionsAndRemovals(): void
    {
        $migrator = $this->container->get(Migrator::class);
        $migrator->updateHistory([M260101000002Second::class], []);
        $before = $migrator->getHistory();
        $this->container->get(Connection::class)->getActivePdo()->exec(
            "CREATE TRIGGER fail_history_delete BEFORE DELETE ON migration
             BEGIN SELECT RAISE(ABORT, 'History deletion failed'); END",
        );

        try {
            $migrator->updateHistory([M260101000003Third::class], [M260101000002Second::class]);
            self::fail('Expected the history deletion to fail.');
        } catch (Exception $exception) {
            self::assertStringContainsString('History deletion failed', $exception->getMessage());
        }

        self::assertSame($before, $migrator->getHistory());
    }
}
