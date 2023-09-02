<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Runner;

use RuntimeException;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Db\Migration\Migrator;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

final class DownRunner
{
    private ?SymfonyStyle $io = null;

    public function __construct(private Migrator $migrator)
    {
    }

    public function setIO(?SymfonyStyle $io): void
    {
        $this->io = $io;
        $this->migrator->setIO($io);
    }

    public function run(RevertibleMigrationInterface $migration): void
    {
        if ($this->io === null) {
            throw new RuntimeException('You need to set output decorator via `setIO()`.');
        }

        $className = $migration::class;

        $this->io->title("\nReverting $className");

        $start = microtime(true);

        $this->migrator->down($migration);

        $time = microtime(true) - $start;
        $this->io->writeln(
            "\n\t<info>>>> [OK] - Reverted $className (time: " . sprintf('%.3f', $time) . 's)</info>'
        );
    }
}
