<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Informer;

use Yiisoft\Yii\Db\Migration\Helper\ConsoleHelper;

/**
 * Writes migration process informational messages into console.
 */
final class ConsoleMigrationInformer implements MigrationInformerInterface
{
    private ConsoleHelper $helper;

    public function __construct(ConsoleHelper $helper)
    {
        $this->helper = $helper;
    }

    public function beginCreateHistoryTable(string $message): void
    {
        $this->helper->io()->section($message);
    }

    public function endCreateHistoryTable(string $message): void
    {
        $this->helper->output()->writeln("\t<fg=green>>>> [OK] - '.$message.'.</>");
    }

    public function beginCommand(string $message): void
    {
        $this->helper->output()->write('    > ' . $message . ' ...');
    }

    public function endCommand(string $message): void
    {
        $this->helper->output()->writeln(' ' . $message);
    }
}
