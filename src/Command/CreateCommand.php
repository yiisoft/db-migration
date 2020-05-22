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
use function file_exists;
use function file_put_contents;
use function in_array;
use function preg_match;
use function strlen;

/**
 * Creates a new migration.
 *
 * This command creates a new migration using the available migration template.
 *
 * Config in di-container console.php migrations paths `createPath` and `updatePath`:
 *
 * ```php
 * MigrationService::class => static function (ContainerInterface $container) {
 *    $aliases = $container->get(Aliases::class);
 *     $db = $container->get(Connection::class);
 *     $consoleHelper = $container->get(ConsoleHelper::class);
 *
 *     $migration = new MigrationService($aliases, $db, $consoleHelper);
 *
 *     $migration->createPath($aliases->get('@migration'));
 *     $migration->updatePath([$aliases->get('@migration'), $aliases->get('@root/src/Build')]);
 *
 *    return $migration;
 * }
 * ```
 *
 * Config in di-container console.php namespace paths `createPath` and `updatePath`:
 *
 * ```php
 * MigrationService::class => static function (ContainerInterface $container) {
 *    $aliases = $container->get(Aliases::class);
 *     $db = $container->get(Connection::class);
 *     $consoleHelper = $container->get(ConsoleHelper::class);
 *
 *     $migration = new MigrationService($aliases, $db, $consoleHelper);
 *
 *     $migration->createNamespace($aliases->get('@migration'));
 *     $migration->updateNamespace(['Yiisoft\\Db\\Yii\Migration', 'App\\Migration')]);
 *
 *    return $migration;
 * }
 * ```
 *
 * After using this command, developers should modify the created migration skeleton by filling up the actual
 * migration logic.
 *
 * ```php
 * vendor/bin/yii migrate/create table --command=table
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
 * vendor/bin/yii migrate/create post --command=table --namespace=Yiisoft\\Yii\Db\\Migration\\Migration
 * ```
 *
 * In case {@see createPath} is not set and no namespace is provided, {@see createNamespace} will be used.
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
            ->addOption('namespace', null, InputOption::VALUE_OPTIONAL, 'Namespace migration')
            ->setHelp('This command Generate migration template.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->migrationService->title();

        if ($this->migrationService->before(static::$defaultName) === ExitCode::DATAERR) {
            return ExitCode::DATAERR;
        }

        $name = (string) $input->getArgument('name');
        $command = (string) $input->getOption('command');
        $fields = (string) $input->getOption('fields');
        $and = (string) $input->getOption('and');
        $namespace = (string) $input->getOption('namespace');

        $table = $name;

        if (!empty($fields)) {
            $this->fields = explode(',', $fields);
        }

        if (!preg_match('/^[\w\\\\]+$/', $name)) {
            $this->consoleHelper->io()->error(
                'The migration name should contain letters, digits, underscore and/or backslash characters only.'
            );

            return ExitCode::DATAERR;
        }

        $availableCommands = ['create', 'table', 'dropTable', 'addColumn', 'dropColumn', 'junction'];
        if (!in_array($command, $availableCommands, true)) {
            $this->consoleHelper->io()->error(
                "Command not found \"$command\". Available commands: " . implode(', ', $availableCommands) . '.'
            );

            return ExitCode::DATAERR;
        }

        $name = $this->generateName($command, $this->consoleHelper->inflector()->camelize($name), $and);

        [$namespace, $className] = $this->migrationService->generateClassName($namespace, $name);

        $nameLimit = $this->migrationService->getMigrationNameLimit();

        if ($nameLimit !== null && strlen($className) > $nameLimit) {
            $this->consoleHelper->io()->error('The migration name is too long.');

            return ExitCode::DATAERR;
        }

        $migrationPath = $this->consoleHelper->aliases()->get(
            FileHelper::normalizePath($this->migrationService->findMigrationPath($namespace))
        );

        $file = $migrationPath . DIRECTORY_SEPARATOR . $className . '.php';

        $helper = $this->getHelper('question');

        if (!file_exists($migrationPath)) {
            $this->consoleHelper->io()->error("Invalid path directory {$migrationPath}");

            return ExitCode::DATAERR;
        }

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
                $result = 'Create_' . $name;
                break;
            case 'table':
                $result = 'Create_' . $name . '_Table';
                break;
            case 'dropTable':
                $result = 'Drop_' . $name . '_Table';
                break;
            case 'addColumn':
                $result = 'Add_Column_' . $name;
                break;
            case 'dropColumn':
                $result = 'Drop_Column_' . $name;
                break;
            case 'junction':
                $result = 'Junction_Table_For_' . $name . '_And_' . $and . '_Tables';
                break;
        }

        return $result;
    }
}
