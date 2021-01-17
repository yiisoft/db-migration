<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Service;

use ReflectionException;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Db\Cache\QueryCache;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Query\Query;
use Yiisoft\Injector\Injector;
use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Db\Migration\Helper\ConsoleHelper;
use Yiisoft\Yii\Db\Migration\MigrationBuilder;
use Yiisoft\Yii\Db\Migration\MigrationInterface;
use Yiisoft\Yii\Db\Migration\RevertibleMigrationInterface;

use function array_slice;

final class MigrationService
{
    /**
     * The name of the dummy migration that marks the beginning of the whole migration history.
     */
    public const BASE_MIGRATION = 'M000000000000Base';

    private string $createNamespace = '';
    private string $createPath = '';
    private array $updateNamespace = [];
    private array $updatePath = [];
    private string $comment = '';
    private bool $compact = false;
    private array $fields = [];
    private array $generatorTemplateFiles = [];
    private int $maxNameLength = 180;
    private int $migrationNameLimit = 0;
    private string $migrationTable = '{{%migration}}';
    private bool $useTablePrefix = true;
    private string $version = '1.0';
    private bool $schemaCacheEnabled = false;
    private bool $queryCacheEnabled = false;
    private ConnectionInterface $db;
    private SchemaCache $schemaCache;
    private QueryCache $queryCache;
    private ConsoleHelper $consoleHelper;
    private Injector $injector;

    public function __construct(
        ConnectionInterface $db,
        SchemaCache $schemaCache,
        QueryCache $queryCache,
        ConsoleHelper $consoleHelper,
        Injector $injector
    ) {
        $this->db = $db;
        $this->schemaCache = $schemaCache;
        $this->queryCache = $queryCache;
        $this->consoleHelper = $consoleHelper;
        $this->injector = $injector;

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
            case 'generate/create':
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

    public function getComment(): string
    {
        return $this->comment;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function getGeneratorTemplateFiles(?string $key): string
    {
        if (!isset($this->generatorTemplateFiles[$key])) {
            throw new InvalidConfigException('You must define a template to generate the migration.');
        }

        return $this->generatorTemplateFiles[$key];
    }

    public function getMigrationNameLimit(): int
    {
        if ($this->migrationNameLimit !== 0) {
            return $this->migrationNameLimit;
        }

        $tableSchema = $this->db->getSchema()->getTableSchema($this->migrationTable, true);

        if ($tableSchema !== null) {
            $nameLimit = $tableSchema->getColumns()['version']->getSize();

            return $nameLimit === null ? 0 : $this->migrationNameLimit = $nameLimit;
        }

        return $this->maxNameLength;
    }

    public function getMigrationHistory(?int $limit = null): array
    {
        if ($this->db->getSchema()->getTableSchema($this->migrationTable, true) === null) {
            $this->createMigrationHistoryTable();
        }

        $query = (new Query($this->db))
            ->select(['version', 'apply_time'])
            ->from($this->migrationTable)
            ->orderBy(['apply_time' => SORT_DESC, 'id' => SORT_DESC]);

        if (empty($this->updateNamespace)) {
            if ($limit > 0) {
                $query->limit($limit);
            }

            $rows = $query->all();

            $history = ArrayHelper::map($rows, 'version', 'apply_time');
            unset($history[self::BASE_MIGRATION]);

            return $history;
        }

        $rows = $query->all();
        $history = [];

        foreach ($rows as $row) {
            if ($row['version'] === self::BASE_MIGRATION) {
                continue;
            }

            $row['apply_time'] = (int)$row['apply_time'];
            $history[] = $row;
        }

        $history = array_slice($history, 0, $limit);
        return ArrayHelper::map($history, 'version', 'apply_time');
    }

    public function getMigrationTable(): string
    {
        return $this->migrationTable;
    }

    /**
     * Returns the migrations that are not applied.
     *
     * @return array list of new migrations
     */
    public function getNewMigrations(): array
    {
        $applied = [];

        foreach ($this->getMigrationHistory() as $class => $time) {
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
     * Set the comment for the table being created.
     *
     * @param string $value
     */
    public function comment(string $value): void
    {
        $this->comment = $value;
    }

    /**
     * Indicates whether the console output should be compacted.
     *
     * @var bool $value
     *
     * If this is set to true, the individual commands ran within the migration will not be output to the console.
     * Default is false, in other words the output is fully verbose by default.
     */
    public function compact(bool $value): void
    {
        $this->compact = $value;
    }

    /**
     * Column definition strings used for creating migration code.
     *
     * The format of each definition is `COLUMN_NAME:COLUMN_TYPE:COLUMN_DECORATOR`. Delimiter is `,`. For example,
     * `--fields="name:string(12):notNull:unique"` produces a string column of size 12 which is not null and unique
     * values.
     *
     * Note: primary key is added automatically and is named id by default. If you want to use another name you may
     * specify it explicitly like `--fields="id_key:primaryKey,name:string(12):notNull:unique"`
     *
     * @param array $value
     */
    public function fields(array $value): void
    {
        $this->fields = $value;
    }

    /**
     * Set maximum length of a migration name.
     *
     * @param int $value
     */
    public function maxNameLength(int $value): void
    {
        $this->maxNameLength = $value;
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
     * Set the name of the table for keeping applied migration information.
     *
     * @param string $value the name of the table for keeping applied migration information.
     */
    public function migrationTable(string $value): void
    {
        $this->migrationTable = $value;
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

    private function addMigrationHistory(string $version): void
    {
        $command = $this->db->createCommand();

        $command->insert($this->migrationTable, [
            'version' => $version,
            'apply_time' => time(),
        ])->execute();
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
     * Creates the migration history table.
     */
    private function createMigrationHistoryTable(): void
    {
        $this->beforeMigrate();

        $tableName = $this->db->getSchema()->getRawTableName($this->migrationTable);

        $this->consoleHelper->io()->section("Creating migration history table \"$tableName\"...");

        $this->db->createCommand()->createTable($this->migrationTable, [
            'id' => 'pk',
            'version' => 'varchar(' . $this->maxNameLength . ') NOT NULL',
            'apply_time' => 'integer',
        ])->execute();
        $this->db->createCommand()->insert($this->migrationTable, [
            'version' => self::BASE_MIGRATION,
            'apply_time' => time(),
        ])->execute();

        $this->consoleHelper->output()->writeln("\t<fg=green>>>> [OK] - Done.</>\n");

        $this->afterMigrate();
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
     * Removes existing migration from the history.
     *
     * @param string $version migration version name.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function removeMigrationHistory(string $version): void
    {
        $command = $this->db->createCommand();
        $command->delete($this->migrationTable, [
            'version' => $version,
        ])->execute();
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

    public function up(MigrationInterface $migration): void
    {
        $this->beforeMigrate();
        $migration->up($this->createBuilder());
        $this->afterMigrate();
        $this->addMigrationHistory(get_class($migration));
    }

    public function down(RevertibleMigrationInterface $migration): void
    {
        $this->beforeMigrate();
        $migration->down($this->createBuilder());
        $this->afterMigrate();
        $this->removeMigrationHistory(get_class($migration));
    }

    private function beforeMigrate(): void
    {
        $this->db->setEnableSlaves(false);

        $this->db->getSchema()->refresh();

        $this->queryCacheEnabled = $this->queryCache->isEnabled();
        if ($this->queryCacheEnabled) {
            $this->queryCache->setEnable(false);
        }

        $this->schemaCacheEnabled = $this->schemaCache->isEnabled();
        if ($this->schemaCacheEnabled) {
            $this->schemaCache->setEnable(false);
        }
    }

    private function afterMigrate(): void
    {
        if ($this->queryCacheEnabled) {
            $this->queryCache->setEnable(true);
        }

        if ($this->schemaCacheEnabled) {
            $this->schemaCache->setEnable(true);
        }

        $this->db->getSchema()->refresh();
    }

    private function createBuilder(): MigrationBuilder
    {
        return new MigrationBuilder(
            $this->db,
            $this->compact
        );
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
