<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Yiisoft\Files\FileHelper;
use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Db\Migration\Helper\ConsoleHelper;
use Yiisoft\Yii\Db\Migration\Service\Generate\CreateService;
use Yiisoft\Yii\Db\Migration\Service\MigrationService;

use function explode;
use function file_exist;
use function file_put_contents;
use function in_array;
use function preg_match;
use function strlen;

/**
 * Creates a new migration.
 *
 * This command creates a new migration using the available migration template.
 *
 * After using this command, developers should modify the created migration skeleton by filling up the actual
 * migration logic.
 *
 * ```php
 * yii migrate/create create_user_table
 * ```
 *
 * In order to generate a namespaced migration, you should specify a namespace before the migration's name.
 *
 * Note that backslash (`\`) is usually considered a special character in the shell, so you need to escape it properly
 * to avoid shell errors or incorrect behavior.
 *
 * For example:
 *
 * ```php
 * yii migrate/create 'app\\migrations\\createUserTable'
 * ```
 *
 * In case {@see migrationPath} is not set and no namespace is provided, the first entry of {@see migrationNamespaces}
 * will be used.
 */
final class CreateCommand extends Command
{
    private ConsoleHelper $consoleHelper;
    private array $fields = [];
    private CreateService $createService;
    private MigrationService $migrationService;

    protected static $defaultName = 'generate/create';

    public function __construct(
        ConsoleHelper $consoleHelper,
        CreateService $createService,
        MigrationService $migrationService
    ) {
        $this->consoleHelper = $consoleHelper;
        $this->createService = $createService;
        $this->migrationService = $migrationService;

        parent::__construct();
    }

    public function configure(): void
    {
        $this
            ->setDescription('Generate migration template.')
            ->addArgument('name', InputArgument::REQUIRED, 'Table name for generate migration.')
            ->addOption('command', 'c', InputOption::VALUE_OPTIONAL, 'Command to execute.', 'create')
            ->addOption('fields', 'f', InputOption::VALUE_OPTIONAL, 'To create table fields right away')
            ->addOption('and', null, InputOption::VALUE_OPTIONAL, 'And junction')
            ->setHelp('This command Generate migration template.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->migrationService->title();
        $this->migrationService->before();

        $name = $input->getArgument('name');
        $command = $input->getOption('command') ?? [];
        $fields = $input->getOption('fields') ?? [];
        $and = $input->getOption('and');

        $table = $name;

        if ($fields !== []) {
            $this->fields = \explode(',', $fields);
        }

        if (!preg_match('/^[\w\\\\]+$/', $name)) {
            $this->consoleHelper->io()->error(
                'The migration name should contain letters, digits, underscore and/or backslash characters only.'
            );

            return ExitCode::DATAERR;
        }

        if (!in_array($command, ['create', 'table', 'dropTable', 'addColumn', 'dropColumn', 'junction'])) {
            $this->consoleHelper->io()->error(
                "Command not found \"$command\". Avaibles: create, table, dropTable, addColumn, dropColumn, junction"
            );

            return ExitCode::DATAERR;
        }

        $name = $this->generateName($command, $name, $and);

        [$namespace, $className] = $this->migrationService->generateClassName($name);
        $nameLimit = $this->migrationService->getMigrationNameLimit();

        if ($nameLimit !== null && strlen($className) > $nameLimit) {
            $this->consoleHelper->io()->error('The migration name is too long.');

            return ExitCode::DATAERR;
        }

        $migrationPath = $this->migrationService->findMigrationPath($namespace);
        $file = $migrationPath . DIRECTORY_SEPARATOR . $className . '.php';
        $helper = $this->getHelper('question');
        $result = false;

        $question = new ConfirmationQuestion(
            "\n<fg=cyan>Create new migration y/n: </>",
            false,
            '/^(y)/i'
        );

        if ($helper->ask($input, $output, $question)) {
            $content = $this->createService->run(
                $command,
                $this->migrationService->getGeneratorTemplateFiles($command),
                $table,
                $className,
                $namespace,
                $this->fields,
                $and
            );

            if (file_exists($migrationPath)) {
                FileHelper::createDirectory($migrationPath);
            }
            file_put_contents($file, $content, LOCK_EX);

            $output->writeln("\n\t<info>$className</info>");
            $output->writeln("\n");
            $this->consoleHelper->io()->success('New migration created successfully.');
        }

        $this->migrationService->dbVersion();

        return ExitCode::OK;
    }

    private function generateName(string $command, string $name, ?string $and): string
    {
        $result = '';

        switch ($command) {
            case 'create':
                $result = 'create_' . $name;
                break;
            case 'table':
                $result = 'create_' . $name . '_table';
                break;
            case 'dropTable':
                $result = 'drop_' . $name . '_table';
                break;
            case 'addColumn':
                $result = 'add_column_' . $name;
                break;
            case 'dropColumn':
                $result = 'drop_column_' . $name;
                break;
            case 'junction':
                $result = 'junction_table_for_' . $name . '_and_' . $and . '_tables';
                break;
        }

        return $result;
    }
}
