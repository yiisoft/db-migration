<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Service;

use function array_slice;
use function array_values;
use function closedir;
use function file_exists;
use function gmdate;
use function is_file;
use function ksort;
use function opendir;
use function preg_match;

use function readdir;
use function str_replace;
use function strcasecmp;
use function strpos;
use function time;
use function trim;
use function usort;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Query\Query;
use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Db\Migration\Helper\ConsoleHelper;
use Yiisoft\Yii\Db\Migration\MigrationInterface;

final class MigrationService
{
    /**
     * The name of the dummy migration that marks the beginning of the whole migration history.
     */
    public const BASE_MIGRATION = 'm000000_000000_base';

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
    private ConnectionInterface $db;
    private ConsoleHelper $consoleHelper;

    public function __construct(ConnectionInterface $db, ConsoleHelper $consoleHelper)
    {
        $this->db = $db;
        $this->consoleHelper = $consoleHelper;

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
            ->orderBy(['apply_time' => SORT_DESC, 'version' => SORT_DESC]);

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

            if (preg_match('/m?(\d{6}_?\d{6})(\D.*)?$/is', $row['version'], $matches)) {
                $time = str_replace('_', '', $matches[1]);
                $row['canonicalVersion'] = $time;
            } else {
                $row['canonicalVersion'] = $row['version'];
            }

            $row['apply_time'] = (int) $row['apply_time'];
            $history[] = $row;
        }

        usort($history, static function (array $a, array $b) {
            if ($a['apply_time'] === $b['apply_time']) {
                if (($compareResult = strcasecmp($b['canonicalVersion'], $a['canonicalVersion'])) !== 0) {
                    return $compareResult;
                }

                return strcasecmp($b['version'], $a['version']);
            }

            return ($a['apply_time'] > $b['apply_time']) ? -1 : +1;
        });

        $history = array_slice($history, 0, $limit);
        $history = ArrayHelper::map($history, 'version', 'apply_time');

        return $history;
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

                if (preg_match('/^(m(\d{6}_?\d{6})\D.*?)\.php$/is', $file, $matches) && is_file($path)) {
                    $class = $matches[1];
                    if (!empty($namespace)) {
                        $class = $namespace . '\\' . $class;
                    }
                    $time = str_replace('_', '', $matches[2]);
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

    public function addMigrationHistory(string $version): void
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
        $migration = null;

        $this->includeMigrationFile($class);

        $class = '\\' . $class;

        if (class_exists($class)) {
            $migration = new $class($this->db);
        }

        if ($migration instanceof MigrationInterface) {
            $migration->compact($this->compact);
        }

        return $migration;
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
        $tableName = $this->db->getSchema()->getRawTableName($this->migrationTable);

        $this->consoleHelper->io()->section("Creating migration history table \"$tableName\"...");

        $this->db->createCommand()->createTable($this->migrationTable, [
            'version' => 'varchar(' . $this->maxNameLength . ') NOT NULL PRIMARY KEY',
            'apply_time' => 'integer',
        ])->execute();
        $this->db->createCommand()->insert($this->migrationTable, [
            'version' => self::BASE_MIGRATION,
            'apply_time' => time(),
        ])->execute();

        $this->consoleHelper->output()->writeln("\t<fg=green>>>> [OK] - Done.</>\n");
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

        if ($namespace === null) {
            $class = 'M' . gmdate('ymd_His') . '_' . $name;
        } else {
            $class = 'M' . gmdate('ymdHis') . $this->consoleHelper->inflector()->toPascalCase($name);
        }

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

        return $this->consoleHelper->getPathFromNameSpace($aliases);
    }
}
