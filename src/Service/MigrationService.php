<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Service;

use ReflectionException;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Injector\Injector;
use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Db\Migration\Helper\ConsoleHelper;
use Yiisoft\Yii\Db\Migration\MigrationInterface;
use Yiisoft\Yii\Db\Migration\Migrator;

final class MigrationService
{
    private string $createNamespace = '';
    private string $createPath = '';
    private array $updateNamespace = [];
    private array $updatePath = [];
    private array $generatorTemplateFiles = [];
    private bool $useTablePrefix = true;
    private string $version = '1.0';
    private ConnectionInterface $db;
    private ConsoleHelper $consoleHelper;
    private Injector $injector;
    private Migrator $migrator;

    public function __construct(
        ConnectionInterface $db,
        ConsoleHelper $consoleHelper,
        Injector $injector,
        Migrator $migrator
    ) {
        $this->db = $db;
        $this->consoleHelper = $consoleHelper;
        $this->injector = $injector;
        $this->migrator = $migrator;

        $this->generatorTemplateFiles();
    }

    /**
     * This method is invoked right before an action is to be executed (after all possible filters.)
     *
     * It checks the existence of the {@see createPath}, {@see updatePath}, {@see createNamespace},
     * {@see updateNamespace}.
     *
     * {@see createNamespace}, {@see updateNamespace}.
     *
     * @param string $defaultName
     *
     * @return int whether the action should continue to be executed.
     */
    public function before(string $defaultName): int
    {
        $result = ExitCode::OK;

        switch ($defaultName) {
            case 'migrate/create':
                if (empty($this->createNamespace) && empty($this->createPath)) {
                    $this->consoleHelper->io()->error(
                        'At least one of `createNamespace` or `createPath` should be specified.'
                    );

                    $result = ExitCode::DATAERR;
                }
                break;
            case 'migrate/up':
                if (empty($this->updateNamespace) && empty($this->updatePath)) {
                    $this->consoleHelper->io()->error(
                        'At least one of `updateNamespace` or `updatePath` should be specified.'
                    );

                    $result = ExitCode::DATAERR;
                }
                break;
        }

        return $result;
    }

    public function getGeneratorTemplateFiles(?string $key): string
    {
        if (!isset($this->generatorTemplateFiles[$key])) {
            throw new InvalidConfigException('You must define a template to generate the migration.');
        }

        return $this->generatorTemplateFiles[$key];
    }

    /**
     * Returns the migrations that are not applied.
     *
     * @return array list of new migrations
     */
    public function getNewMigrations(): array
    {
        $applied = [];

        foreach ($this->migrator->getHistory() as $class => $time) {
            $applied[trim($class, '\\')] = true;
        }

        $migrationPaths = [];

        foreach ($this->updatePath as $path) {
            $migrationPaths[] = [$path, ''];
        }

        foreach ($this->updateNamespace as $namespace) {
            $migrationPaths[] = [$this->getNamespacePath($namespace), $namespace];
        }

        $migrations = [];
        foreach ($migrationPaths as $item) {
            [$updatePath, $namespace] = $item;
            $updatePath = $this->consoleHelper->aliases()->get($updatePath);

            if (!file_exists($updatePath)) {
                continue;
            }

            $handle = opendir($updatePath);
            while (($file = readdir($handle)) !== false) {
                if ($file === '.' || $file === '..') {
                    continue;
                }

                $path = $updatePath . DIRECTORY_SEPARATOR . $file;

                if (preg_match('/^(M(\d{12}).*)\.php$/s', $file, $matches) && is_file($path)) {
                    $class = $matches[1];
                    if (!empty($namespace)) {
                        $class = $namespace . '\\' . $class;
                    }
                    $time = $matches[2];
                    if (!isset($applied[$class])) {
                        $migrations[$time . '\\' . $class] = $class;
                    }
                }
            }
            closedir($handle);
        }
        ksort($migrations);

        return array_values($migrations);
    }

    public function getUseTablePrefix(): bool
    {
        return $this->useTablePrefix;
    }

    /**
     * List of namespaces containing the migration update classes.
     *
     * @param array $value
     *
     * Migration namespaces should be resolvable as a [path alias](guide:concept-aliases) if prefixed with `@`, e.g.
     * if you specify the namespace `app\migrations`, the code `$this->aliases->get('@app/migrations')` should be able
     * to return the file path to the directory this namespace refers to.
     *
     * This corresponds with the [autoloading conventions](guide:concept-autoloading) of Yii.
     */
    public function updateNamespace(array $value): void
    {
        $this->updateNamespace = $value;
    }

    /**
     * The directory containing the migration update classes.
     *
     * This can be either a [path alias](guide:concept-aliases) or a directory path.
     *
     * Migration classes located at this path should be declared without a namespace.
     * Use {@see createNamespace} property in case you are using namespaced migrations.
     *
     * If you have set up {createNamespace}, you may set this field to `null` in order to disable usage of  migrations
     * that are not namespaced.
     *
     * In general, to load migrations from different locations, {createNamespace} is the preferable solution as the
     * migration name contains the origin of the migration in the history, which is not the case when using multiple
     * migration paths.
     *
     * @param array $value
     *
     * {@see $createNamespace}
     */
    public function updatePath(array $value): void
    {
        $this->updatePath = $value;
    }

    /**
     * Indicates whether the table names generated should consider the `tablePrefix` setting of the DB connection.
     *
     * For example, if the table name is `post` the generator wil return `{{%post}}`.
     *
     * @param bool $value
     */
    public function useTablePrefix(bool $value): void
    {
        $this->useTablePrefix = $value;
    }

    public function version(): string
    {
        return $this->version;
    }

    public function dbVersion(): void
    {
        $this->consoleHelper->output()->writeln(
            "<fg=cyan>\nDriver: {$this->db->getDrivername()} {$this->db->getServerVersion()}.</>"
        );
    }

    /**
     * Creates a new migration instance.
     *
     * @psalm-suppress MoreSpecificReturnType
     * @psalm-suppress LessSpecificReturnStatement
     *
     * @param string $class the migration class name
     *
     * @return MigrationInterface|null the migration instance
     */
    public function createMigration(string $class): ?MigrationInterface
    {
        $this->includeMigrationFile($class);
        $class = '\\' . $class;

        try {
            return $this->injector->make($class);
        } catch (ReflectionException $e) {
            return null;
        }
    }

    public function createNamespace(string $value): void
    {
        $this->createNamespace = $value;
    }

    public function createPath(string $value): void
    {
        $this->createPath = $value;
    }

    /**
     * Includes the migration file for a given migration class name.
     *
     * This function will do nothing on namespaced migrations, which are loaded by autoloading automatically. It will
     * include the migration file, by searching {@see updatePath} for classes without namespace.
     *
     * @param string $class the migration class name.
     */
    private function includeMigrationFile(string $class): void
    {
        $class = trim($class, '\\');
        if (strpos($class, '\\') === false) {
            foreach ($this->updatePath as $path) {
                $file = $this->consoleHelper->aliases()->get($path) . DIRECTORY_SEPARATOR . $class . '.php';

                if (is_file($file)) {
                    /** @psalm-suppress UnresolvableInclude */
                    require_once $file;
                    break;
                }
            }
        }
    }

    /**
     * Generates class base name and namespace from migration name from user input.
     *
     * @param string|null $namespace migration.
     * @param string $name migration name from user input.
     *
     * @return array list of 2 elements: 'namespace' and 'class base name'.
     */
    public function generateClassName(?string $namespace, string $name): array
    {
        if (empty($this->createPath) && empty($namespace)) {
            $namespace = $this->createNamespace;
        }

        $class = 'M' . gmdate('ymdHis') . $this->consoleHelper->inflector()->toPascalCase($name);

        return [$namespace, $class];
    }

    /**
     * Finds the file path for the specified migration namespace.
     *
     * @param string|null $namespace migration namespace.
     *
     * @return string migration file path.
     */
    public function findMigrationPath(?string $namespace): string
    {
        $namespace = $namespace ?? $this->createNamespace;

        if (empty($namespace)) {
            return $this->createPath;
        }

        return $this->getNamespacePath($namespace);
    }

    /**
     * Set of template paths for generating migration code automatically.
     *
     * @param string $key
     * @param string $value
     *
     * The key is the template type, the value is a path or the alias.
     *
     * Supported types are:
     *
     * ```php
     *   'create' => '@yiisoft/yii/db/migration/resources/views/migration.php',
     *   'table' => '@yiisoft/yii/db/migration/resources/views/createTableMigration.php',
     *   'dropTable' => '@yiisoft/yii/db/migration/resources/views/dropTableMigration.php',
     *   'addColumn' => '@yiisoft/yii/db/migration/resources/views/addColumnMigration.php',
     *   'dropColumn' => '@yiisoft/yii/db/migration/resources/views/dropColumnMigration.php',
     *   'junction' => '@yiisoft/yii/db/migration/resources/views/createTableMigration.php'
     *```
     */
    public function generatorTemplateFile(string $key, string $value): void
    {
        $this->generatorTemplateFiles[$key] = $value;
    }

    public function generatorTemplateFiles(array $value = []): void
    {
        $this->generatorTemplateFiles = $value;

        if ($value === [] && $this->generatorTemplateFiles === []) {
            $this->generatorTemplateFiles = [
                'create' => $this->consoleHelper->getBaseDir() . '/resources/views/migration.php',
                'table' => $this->consoleHelper->getBaseDir() . '/resources/views/createTableMigration.php',
                'dropTable' => $this->consoleHelper->getBaseDir() . '/resources/views/dropTableMigration.php',
                'addColumn' => $this->consoleHelper->getBaseDir() . '/resources/views/addColumnMigration.php',
                'dropColumn' => $this->consoleHelper->getBaseDir() . '/resources/views/dropColumnMigration.php',
                'junction' => $this->consoleHelper->getBaseDir() . '/resources/views/createTableMigration.php',
            ];
        }
    }

    /**
     * Returns the file path matching the give namespace.
     *
     * @param string $namespace namespace.
     *
     * @return string file path.
     */
    private function getNamespacePath(string $namespace): string
    {
        $aliases = '@' . str_replace('\\', '/', $namespace);

        return $this->consoleHelper->getPathFromNamespace($aliases);
    }
}
