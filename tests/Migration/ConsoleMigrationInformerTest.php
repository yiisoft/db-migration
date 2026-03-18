<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Migration;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Db\Migration\Informer\ConsoleMigrationInformer;

final class ConsoleMigrationInformerTest extends TestCase
{
    public function testBeginCreateHistoryTableWithoutIo(): void
    {
        $informer = new ConsoleMigrationInformer();

        $informer->beginCreateHistoryTable('Creating migration history table...');

        $this->expectNotToPerformAssertions();
    }

    public function testBeginCreateHistoryTableWithIo(): void
    {
        $io = $this->createMock(SymfonyStyle::class);
        $io->expects($this->once())->method('write')->with('Creating migration history table...');

        $informer = new ConsoleMigrationInformer();
        $informer->setIo($io);

        $informer->beginCreateHistoryTable('Creating migration history table...');
    }

    public function testEndCreateHistoryTable(): void
    {
        $informer = new ConsoleMigrationInformer();

        $informer->endCreateHistoryTable('Done.');

        $this->expectNotToPerformAssertions();
    }

    public function testBeginCommandWithoutIo(): void
    {
        $informer = new ConsoleMigrationInformer();

        $informer->beginCommand('create table test');

        $this->expectNotToPerformAssertions();
    }

    public function testBeginCommandWithIo(): void
    {
        $io = $this->createMock(SymfonyStyle::class);
        $io->expects($this->once())->method('write')->with('    > create table test ...');

        $informer = new ConsoleMigrationInformer();
        $informer->setIo($io);

        $informer->beginCommand('create table test');
    }

    public function testEndCommandWithoutIo(): void
    {
        $informer = new ConsoleMigrationInformer();

        $informer->endCommand('done (time: 0.001s)');

        $this->expectNotToPerformAssertions();
    }

    public function testEndCommandWithIo(): void
    {
        $io = $this->createMock(SymfonyStyle::class);
        $io->expects($this->once())->method('writeln')->with(' done (time: 0.001s)');

        $informer = new ConsoleMigrationInformer();
        $informer->setIo($io);

        $informer->endCommand('done (time: 0.001s)');
    }

    public function testSetIoWithNull(): void
    {
        $informer = new ConsoleMigrationInformer();

        $informer->setIo(null);

        $this->expectNotToPerformAssertions();
    }
}
