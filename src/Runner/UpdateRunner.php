<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Runner;

use RuntimeException;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Yii\Db\Migration\MigrationInterface;
use Yiisoft\Yii\Db\Migration\Migrator;

use function get_class;

final class UpdateRunner
{
    private Migrator $migrator;
    private ?SymfonyStyle $io = null;

    public function __construct(Migrator $migrator)
    {
        $this->migrator = $migrator;
    }

    public function setIO(?SymfonyStyle $io): void
    {
        $this->io = $io;
        $this->migrator->setIO($io);
    }

    public function run(MigrationInterface $migration): void
    {
        if ($this->io === null) {
            throw new RuntimeException('Need set output decorator via `setIO()`.');
        }

        $className = get_class($migration);

        $this->io->title("\nApplying $className");

        $start = microtime(true);

        $this->migrator->up($migration);

        $time = microtime(true) - $start;
        $this->io->writeln(
            "\n\t<info>>>> [OK] - Applied $className (time: " . sprintf('%.3f', $time) . 's)</info>'
        );
    }
}
