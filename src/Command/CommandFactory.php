<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Command;

use InvalidArgumentException;
use LogicException;
use Yiisoft\Db\Migration\DatabaseSetRegistry;

/**
 * Creates commands using only named database sets, including an explicit default set.
 */
final class CommandFactory
{
    public function __construct(private readonly DatabaseSetRegistry $databases) {}

    public function create(string $name): DatabaseCommand
    {
        $context = $this->databases->createContext('default')
            ?? throw new LogicException('Configure databases["default"] when using CommandFactory.');
        $service = $context->migrationService;
        $migrator = $context->migrator;

        return match ($name) {
            'create' => new CreateCommand($context->createService, $service, $migrator, $this->databases),
            'down' => new DownCommand($context->downRunner, $service, $migrator, $this->databases),
            'history' => new HistoryCommand($service, $migrator, $this->databases),
            'mark' => new MarkCommand($service, $migrator, $this->databases),
            'new' => new NewCommand($service, $migrator, $this->databases),
            'redo' => new RedoCommand($service, $migrator, $context->downRunner, $context->updateRunner, $this->databases),
            'up' => new UpdateCommand($context->updateRunner, $service, $migrator, $this->databases),
            default => throw new InvalidArgumentException('Unknown migration command: ' . $name . '.'),
        };
    }
}
