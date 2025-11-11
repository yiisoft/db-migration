<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Support\Helper;

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Yii\Console\Application;
use Yiisoft\Yii\Console\CommandLoader;

final class CommandHelper
{
    /**
     * @psalm-param class-string<Command> $commandClass
     */
    public static function getCommandTester(ContainerInterface $container, string $commandClass): CommandTester
    {
        $commandName = $commandClass::getDefaultName();
        $application = new Application();
        $application->setCommandLoader(
            new CommandLoader(
                $container,
                [$commandName => $commandClass],
            ),
        );

        return new CommandTester(
            $application->find($commandName),
        );
    }
}
