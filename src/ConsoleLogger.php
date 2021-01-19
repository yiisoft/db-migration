<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Yii\Db\Migration\Helper\ConsoleHelper;

final class ConsoleLogger implements LoggerInterface
{
    use LoggerTrait;

    private ConsoleHelper $helper;

    public function __construct(ConsoleHelper $helper)
    {
        $this->helper = $helper;
    }

    public function log($level, $message, array $context = array())
    {
        switch (ArrayHelper::getValue($context, 'type')) {
            case Migrator::BEGIN_CREATE_HISTORY_TABLE:
                $this->helper->io()->section($message);
                break;

            case Migrator::END_CREATE_HISTORY_TABLE:
                $this->helper->output()->writeln("\t<fg=green>>>> [OK] - '.$message.'.</>\n");
                break;

            case Migrator::BEGIN_COMMAND:
                echo '    > ' . $message . ' ...';
                break;

            case Migrator::END_COMMAND:
                echo ' ' . $message . "\n";
                break;
        }
    }
}
