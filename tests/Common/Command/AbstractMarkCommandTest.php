<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Common\Command;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Db\Migration\Command\MarkCommand;
use Yiisoft\Db\Migration\Migrator;
use Yiisoft\Db\Migration\Service\MigrationService;
use Yiisoft\Db\Migration\Tests\Support\MarkMigrations\M260101000001First;
use Yiisoft\Db\Migration\Tests\Support\MarkMigrations\M260101000002Second;
use Yiisoft\Db\Migration\Tests\Support\MarkMigrations\M260101000003Third;

use function array_keys;
use function dirname;

abstract class AbstractMarkCommandTest extends TestCase
{
    private const FIRST = M260101000001First::class;
    private const SECOND = M260101000002Second::class;
    private const THIRD = M260101000003Third::class;
    protected ContainerInterface $container;

    public static function versions(): array
    {
        return [[self::SECOND], ['\\' . self::SECOND], ['260101000002'], ['260101_000002']];
    }

    #[DataProvider('versions')]
    public function testMarkUpWithoutExecutingMigrations(string $version): void
    {
        $command = $this->command();
        self::assertSame(Command::SUCCESS, $command->execute(['version' => $version, '-y' => true]));
        self::assertSame([self::SECOND, self::FIRST], array_keys($this->migrator()->getHistory()));
        self::assertSame([self::THIRD], $this->container->get(MigrationService::class)->getNewMigrations());
        self::assertStringContainsString('No actual migration was performed', preg_replace('/\s+/', ' ', $command->getDisplay()));
    }

    public function testMarkDownUsesHistoryOrderAndPreservesTarget(): void
    {
        // Application order may differ from filename order, and recorded files may no longer exist.
        $this->migrator()->addMigrationToHistory(self::SECOND);
        $this->migrator()->addMigrationToHistory(self::FIRST);
        $this->migrator()->addMigrationToHistory('M260101000004Missing');
        $before = $this->migrator()->getHistory();
        self::assertSame(Command::SUCCESS, $this->command()->execute(['version' => self::SECOND, '-y' => true]));
        self::assertSame([self::SECOND => $before[self::SECOND]], $this->migrator()->getHistory());
    }

    public function testRecordedTargetDoesNotNeedFile(): void
    {
        $this->migrator()->addMigrationToHistory('M260101000004Missing');
        $this->migrator()->addMigrationToHistory(self::THIRD);
        self::assertSame(Command::SUCCESS, $this->command()->execute(['version' => 'M260101000004Missing', '-y' => true]));
        self::assertSame(['M260101000004Missing'], array_keys($this->migrator()->getHistory()));
    }

    public function testResetHistory(): void
    {
        $this->migrator()->addMigrationToHistory(self::FIRST);
        $command = $this->command();
        self::assertSame(Command::SUCCESS, $command->execute(['version' => MarkCommand::BASE_MIGRATION, '-y' => true]));
        self::assertSame([], $this->migrator()->getHistory());
        self::assertSame(Command::SUCCESS, $command->execute(['version' => MarkCommand::BASE_MIGRATION]));
        self::assertStringContainsString('Nothing needs to be done', $command->getDisplay());
    }

    public function testAlreadyAtTarget(): void
    {
        $this->migrator()->addMigrationToHistory(self::SECOND);
        $before = $this->migrator()->getHistory();
        $command = $this->command();
        self::assertSame(Command::SUCCESS, $command->execute(['version' => self::SECOND, '-y' => true]));
        self::assertSame($before, $this->migrator()->getHistory());
        self::assertStringContainsString('Nothing needs to be done', $command->getDisplay());
    }

    public function testMarkUpPreservesExistingHistory(): void
    {
        $this->migrator()->addMigrationToHistory(self::FIRST);
        $this->migrator()->addMigrationToHistory(self::THIRD);
        $before = $this->migrator()->getHistory();
        self::assertSame(Command::SUCCESS, $this->command()->execute(['version' => self::SECOND, '-y' => true]));
        $after = $this->migrator()->getHistory();
        self::assertCount(3, $after);
        self::assertSame($before[self::FIRST], $after[self::FIRST]);
        self::assertSame($before[self::THIRD], $after[self::THIRD]);
        self::assertSame(self::SECOND, array_keys($after)[0]);
    }

    public function testDecliningMarkDownKeepsHistory(): void
    {
        $this->migrator()->addMigrationToHistory(self::FIRST);
        $this->migrator()->addMigrationToHistory(self::SECOND);
        $before = $this->migrator()->getHistory();
        $command = $this->command();
        $command->setInputs(['no']);
        self::assertSame(Command::SUCCESS, $command->execute(['version' => self::FIRST]));
        self::assertSame($before, $this->migrator()->getHistory());
    }

    public static function invalidVersions(): array
    {
        return [[''], ['invalid'], ['2026-01-01'], ['1767225600'], ['260101000099'], ['M260101000002Unknown']];
    }

    #[DataProvider('invalidVersions')]
    public function testInvalidOrMissingTarget(string $version): void
    {
        $this->migrator()->addMigrationToHistory(self::FIRST);
        $before = $this->migrator()->getHistory();
        self::assertSame(Command::INVALID, $this->command()->execute(['version' => $version, '-y' => true]));
        self::assertSame($before, $this->migrator()->getHistory());
    }

    public static function answers(): array
    {
        return [['no', false], ['', false], ['yes', true]];
    }

    #[DataProvider('answers')]
    public function testConfirmation(string $answer, bool $marked): void
    {
        $command = $this->command();
        $command->setInputs([$answer]);
        self::assertSame(Command::SUCCESS, $command->execute(['version' => self::FIRST]));
        self::assertSame($marked ? [self::FIRST] : [], array_keys($this->migrator()->getHistory()));
    }

    private function command(): CommandTester
    {
        $service = $this->container->get(MigrationService::class);
        $service->setSourcePaths([dirname(__DIR__, 2) . '/Support/MarkMigrations']);
        return new CommandTester(new MarkCommand($service, $this->migrator()));
    }

    private function migrator(): Migrator
    {
        return $this->container->get(Migrator::class);
    }
}
