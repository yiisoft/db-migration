<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Support\Stub;

use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Db\Migration\Informer\MigrationInformerInterface;

final class StubMigrationInformer implements MigrationInformerInterface
{
    private string $output = '';

    public function beginCreateHistoryTable(string $message): void
    {
        $this->output .= $message;
    }

    public function endCreateHistoryTable(string $message): void
    {
        $this->output .= $message;
    }

    public function beginCommand(string $message): void
    {
        $this->output .= '    > ' . $message . ' ...';
    }

    public function endCommand(string $message): void
    {
        $this->output .= ' ' . $message;
    }

    public function getOutput(): string
    {
        return $this->output;
    }

    public function setIO(?SymfonyStyle $io): void
    {
        // do nothing
    }
}
