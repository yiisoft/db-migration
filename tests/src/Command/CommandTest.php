<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Command;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\CommandLoader\ContainerCommandLoader;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Yii\Db\Migration\Helper\ConsoleHelper;
use Yiisoft\Yii\Db\Migration\Tests\BaseTest;

abstract class CommandTest extends BaseTest
{
    protected function getApplication(): Application
    {
        $container = $this->getContainer();
        $application = $container->get(Application::class);

        $loader = new ContainerCommandLoader(
            $container,
            $this->getParams()['yiisoft/yii-console']['commands']
        );
        $application->setCommandLoader($loader);

        $this->getConsoleHelper()->output()->setVerbosity(OutputInterface::VERBOSITY_QUIET);

        return $application;
    }

    protected function getConsoleHelper(): ConsoleHelper
    {
        return $this->getContainer()->get(ConsoleHelper::class);
    }
}
