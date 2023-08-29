<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Runner;

use RuntimeException;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Yii\Db\Migration\MigrationInterface;
use Yiisoft\Yii\Db\Migration\Migrator;

final class UpdateRunner
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

    public function run(MigrationInterface $migration): void
    {
        if ($this->io === null) {
            throw new RuntimeException('You need to set output decorator via `setIO()`.');
        }

        $className = $migration::class;

        $this->io->title("\nApplying $className");

        $start = microtime(true);

        $this->migrator->up($migration);

        $time = microtime(true) - $start;
        $timeWord = sprintf('%.3f', $time);
        $this->io->writeln("\n\t<info>>>> [OK] - Applied $className (time: {$timeWord}s)</info>");
    }
}
