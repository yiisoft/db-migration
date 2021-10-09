<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Support\Helper;

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Yii\Console\Application;
use Yiisoft\Yii\Console\CommandLoader;

final class CommandHelper
{
    /**
     * @psalm-param class-string $commandClass
     */
    public static function getCommandTester(ContainerInterface $container, string $commandClass): CommandTester
    {
        $application = new Application();
        $application->setCommandLoader(
            new CommandLoader(
                $container,
                ['test-command' => $commandClass]
            )
        );

        return new CommandTester(
            $application->find('test-command')
        );
    }
}
