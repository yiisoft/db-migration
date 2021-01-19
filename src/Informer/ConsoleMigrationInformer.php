<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Informer;

use Yiisoft\Yii\Db\Migration\Helper\ConsoleHelper;

final class ConsoleMigrationInformer implements MigrationInformerInterface
{
    private ConsoleHelper $helper;

    public function __construct(ConsoleHelper $helper)
    {
        $this->helper = $helper;
    }

    public function info(int $type, string $message): void
    {
        switch ($type) {
            case MigrationInformerType::BEGIN_CREATE_HISTORY_TABLE:
                $this->helper->io()->section($message);
                break;

            case MigrationInformerType::END_CREATE_HISTORY_TABLE:
                $this->helper->output()->writeln("\t<fg=green>>>> [OK] - '.$message.'.</>\n");
                break;

            case MigrationInformerType::BEGIN_COMMAND:
                echo '    > ' . $message . ' ...';
                break;

            case MigrationInformerType::END_COMMAND:
                echo ' ' . $message . "\n";
                break;
        }
    }
}
