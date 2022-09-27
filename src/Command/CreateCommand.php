<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Files\FileHelper;
use Yiisoft\Strings\Inflector;
use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Db\Migration\Migrator;
use Yiisoft\Yii\Db\Migration\Service\Generate\CreateService;
use Yiisoft\Yii\Db\Migration\Service\MigrationService;

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
 * Config in di-container console.php migrations paths `createPath` and `updatePaths`:
 *
 * ```php
 * MigrationService::class => static function (ContainerInterface $container) {
 *    $aliases = $container->get(Aliases::class);
 *     $db = $container->get(ConnectionInterface::class);
 *     $consoleHelper = $container->get(ConsoleHelper::class);
 *
 *     $migration = new MigrationService($aliases, $db, $consoleHelper);
 *
 *     $migration->createPath($aliases->get('@migration'));
 *     $migration->updatePaths([$aliases->get('@migration'), $aliases->get('@root/src/Build')]);
 *
 *    return $migration;
 * }
 * ```
 *
 * Config in di-container console.php namespace paths `createPath` and `updatePaths`:
 *
 * ```php
 * MigrationService::class => static function (ContainerInterface $container) {
 *    $aliases = $container->get(Aliases::class);
 *     $db = $container->get(ConnectionInterface::class);
 *     $consoleHelper = $container->get(ConsoleHelper::class);
 *
 *     $migration = new MigrationService($aliases, $db, $consoleHelper);
 *
 *     $migration->createNamespace($aliases->get('@migration'));
 *     $migration->updateNamespaces(['Yiisoft\\Db\\Yii\Migration', 'App\\Migration')]);
 *
 *    return $migration;
 * }
 * ```
 *
 * After using this command, developers should modify the created migration skeleton by filling up the actual
 * migration logic.
 *
 * ```php
 * ./yii migrate/create table --command=table
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
 * ./yii migrate/create post --command=table --namespace=Yiisoft\\Yii\Db\\Migration\\Migration
 * ```
 *
 * In case {@see createPath} is not set and no namespace is provided, {@see createNamespace} will be used.
 */
final class CreateCommand extends Command
{
    protected static $defaultName = 'migrate/create';
    protected static $defaultDescription = 'Generate migration template.';

    public function __construct(
        private CreateService $createService,
        private MigrationService $migrationService,
        private Migrator $migrator
    ) {
        parent::__construct();
    }

    public function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Table name for generate migration.')
            ->addOption('command', 'c', InputOption::VALUE_OPTIONAL, 'Command to execute.', 'create')
            ->addOption('fields', 'f', InputOption::VALUE_OPTIONAL, 'To create table fields right away')
            ->addOption('table-comment', null, InputOption::VALUE_OPTIONAL, 'Table comment')
            ->addOption('and', null, InputOption::VALUE_OPTIONAL, 'And junction')
            ->addOption('namespace', null, InputOption::VALUE_OPTIONAL, 'Namespace migration')
            ->setHelp('This command Generate migration template.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->migrator->setIO($io);
        $this->migrationService->setIO($io);
        $this->createService->setIO($io);

        if ($this->migrationService->before(self::$defaultName) === ExitCode::DATAERR) {
            return ExitCode::DATAERR;
        }

        /** @var string */
        $name = $input->getArgument('name');

        /** @var string */
        $table = $name;

        /** @var string */
        $command = $input->getOption('command');

        $fields = $input->hasOption('fields') ? (string) $input->getOption('fields') : null;
        $tableComment = $input->hasOption('table-comment') ? (string) $input->getOption('table-comment') : null;

        /** @var string */
        $and = $input->getOption('and');

        /** @var string */
        $namespace = $input->getOption('namespace');

        if (!preg_match('/^[\w\\\\]+$/', $name)) {
            $io->error(
                'The migration name should contain letters, digits, underscore and/or backslash characters only.'
            );

            return ExitCode::DATAERR;
        }

        $availableCommands = ['create', 'table', 'dropTable', 'addColumn', 'dropColumn', 'junction'];

        if (!in_array($command, $availableCommands, true)) {
            $io->error(
                "Command not found \"$command\". Available commands: " . implode(', ', $availableCommands) . '.'
            );

            return ExitCode::DATAERR;
        }

        $name = $this->generateName($command, (new Inflector())->toPascalCase($name), $and);

        [$namespace, $className] = $this->migrationService->generateClassName($namespace, $name);

        $nameLimit = $this->migrator->getMigrationNameLimit();

        if ($nameLimit !== 0 && strlen($className) > $nameLimit) {
            $io->error('The migration name is too long.');

            return ExitCode::DATAERR;
        }

        $migrationPath = FileHelper::normalizePath($this->migrationService->findMigrationPath($namespace));

        $file = $migrationPath . DIRECTORY_SEPARATOR . $className . '.php';

        $helper = $this->getHelper('question');

        if (!file_exists($migrationPath)) {
            $io->error("Invalid path directory {$migrationPath}");

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
                $table,
                $className,
                $namespace,
                $fields,
                $and,
                $tableComment,
            );

            file_put_contents($file, $content, LOCK_EX);

            $output->writeln("\n\t<info>$className</info>");
            $output->writeln("\n");
            $io->success('New migration created successfully.');
        }

        $this->migrationService->databaseConnection();

        return ExitCode::OK;
    }

    private function generateName(string $command, string $name, ?string $and): string
    {
        $result = '';

        return match ($command) {
            'create' => $name,
            'table' => 'Create_' . $name . '_Table',
            'dropTable' => 'Drop_' . $name . '_Table',
            'addColumn' => 'Add_Column_' . $name,
            'dropColumn' => 'Drop_Column_' . $name,
            'junction' => 'Junction_Table_For_' . $name . '_And_' . $and . '_Tables',
            default => $result,
        };
    }
}
