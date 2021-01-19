<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Informer;

use Yiisoft\Yii\Db\Migration\Helper\ConsoleHelper;

final class ConsoleInformer implements InformerInterface
{
    private ConsoleHelper $helper;

    public function __construct(ConsoleHelper $helper)
    {
        $this->helper = $helper;
    }

    public function info(int $type, string $message): void
    {
        switch ($type) {
            case InformerType::BEGIN_CREATE_HISTORY_TABLE:
                $this->helper->io()->section($message);
                break;

            case InformerType::END_CREATE_HISTORY_TABLE:
                $this->helper->output()->writeln("\t<fg=green>>>> [OK] - '.$message.'.</>\n");
                break;

            case InformerType::BEGIN_COMMAND:
                echo '    > ' . $message . ' ...';
                break;

            case InformerType::END_COMMAND:
                echo ' ' . $message . "\n";
                break;
        }
    }
}
