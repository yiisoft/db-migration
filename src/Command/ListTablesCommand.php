<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Yii\Db\Migration\Service\Database\ListTablesService;

/**
 * Lists all tables in the database.
 */
final class ListTablesCommand extends Command
{
    private ListTablesService $listTablesService;

    protected static $defaultName = 'database/list';

    public function __construct(ListTablesService $listTablesService)
    {
        $this->listTablesService = $listTablesService;

        parent::__construct();
    }

    public function configure(): void
    {
        $this->setDescription('Lists all tables in the database.')
            ->setHelp('This command Lists all tables in the database.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        return $this->listTablesService->withIO($io)->run();
    }
}
