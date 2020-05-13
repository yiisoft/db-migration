<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Yii\Db\Migration\Service\DatabaseService;
use Yiisoft\Yii\Console\ExitCode;

/**
 * Lists all tables in the database.
 */
final class ListTableCommand extends Command
{
    private DatabaseService $databaseService;

    protected static $defaultName = 'database/list';

    public function __construct(DatabaseService $databaseService)
    {
        $this->databaseService = $databaseService;

        parent::__construct();
    }

    public function configure(): void
    {
        $this->setDescription('Lists all tables in the database.')
             ->setHelp('This command Lists all tables in the database.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->databaseService->listTables();

        return ExitCode::OK;
    }
}
