<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Support\Helper;

use LogicException;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Yii\Console\Application;
use Yiisoft\Yii\Console\CommandLoader;

use function sprintf;

final class CommandHelper
{
    /**
     * @psalm-param class-string<Command> $commandClass
     */
    public static function getCommandTester(ContainerInterface $container, string $commandClass): CommandTester
    {
        if ($attribute = (new ReflectionClass($commandClass))->getAttributes(AsCommand::class)) {
            $commandName = $attribute[0]->newInstance()->name;
        } else {
            throw new LogicException(
                sprintf('The command "%s" does not have the AsCommand attribute.', $commandClass),
            );
        }

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
