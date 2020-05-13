<?php

namespace Yiisoft\Yii\Db\Migration\Service;

use Yiisoft\Aliases\Aliases;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Db\Connection\Connection;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Query\Query;
use Yiisoft\Files\FileHelper;
use Yiisoft\Yii\Db\Migration\MigrationInterface;
use Yiisoft\Yii\Db\Migration\Helper\ConsoleHelper;

use function array_slice;
use function array_values;
use function closedir;
use function file_exists;
use function is_array;
use function is_dir;
use function is_file;
use function ksort;
use function opendir;
use function preg_match;
use function readdir;
use function str_replace;
use function strcasecmp;
use function trim;
use function usort;

final class MigrationService
{
    /**
     * The name of the dummy migration that marks the beginning of the whole migration history.
     */
    public const BASE_MIGRATION = 'm000000_000000_base';

    private string $comment = '';
    private bool $compact = false;
    private array $fields = [];
    private array $generatorTemplateFiles = [
        'create' => '@views/migration.php',
        'table' => '@views/createTableMigration.php',
        'dropTable' => '@views/dropTableMigration.php',
        'addColumn' => '@views/addColumnMigration.php',
        'dropColumn' => '@views/dropColumnMigration.php',
        'junction' => '@views/createTableMigration.php',
    ];
    private int $maxNameLength = 180;
    private ?int $migrationNameLimit = null;
    private array $migrationNamespaces = [];
    private array $migrationPath = ['@migration'];
    private string $migrationTable = '{{%migration}}';
    private string $templateFile = '@views/migration.php';
    private bool $useTablePrefix = true;
    private string $version = '1.0';
    private Aliases $aliases;
    private Connection $db;
    private ConsoleHelper $consoleHelper;

    public function __construct(Aliases $aliases, Connection $db, ConsoleHelper $consoleHelper)
    {
        $this->aliases = $aliases;
        $this->db = $db;
        $this->consoleHelper = $consoleHelper;
    }

    /**
     * This method is invoked right before an action is to be executed (after all possible filters.)
     *
     * It checks the existence of the {@see migrationPath}.
     *
     * @throws InvalidConfigException if directory specified in migrationPath doesn't exist and action isn't "create".
     *
     * @return bool whether the action should continue to be executed.
     */
    public function before(): bool
    {
        if (empty($this->migrationNamespaces) && empty($this->migrationPath)) {
            throw new InvalidConfigException(
                'At least one of `migrationPath` or `migrationNamespaces` should be specified.'
            );
        }

        foreach ($this->migrationNamespaces as $key => $value) {
            $this->migrationNamespaces[$key] = trim($value, '\\');
        }

        if (is_array($this->migrationPath)) {
            foreach ($this->migrationPath as $i => $path) {
                $this->migrationPath[$i] = $this->aliases->get($path);
            }
        } elseif ($this->migrationPath !== null) {
            $path = $this->aliases->get($this->migrationPath);
            if (!is_dir($path)) {
                if (self::$defaultName !== 'migrate/create') {
                    throw new InvalidConfigException(
                        "Migration failed. Directory specified in migrationPath doesn't exist: {$this->migrationPath}"
                    );
                }
                @FileHelper::createDirectory($path);
            }
            $this->migrationPath = $path;
        }

        return true;
    }

    /**
     * Set of template paths for generating migration code automatically.
     *
     * @param array $value
     *
     * The key is the template type, the value is a path or the alias. Supported types are:
     * - `create_table`: table creating template
     * - `drop_table`: table dropping template
     * - `add_column`: adding new column template
     * - `drop_column`: dropping column template
     * - `create_junction`: create junction template
     */
    public function generatorTemplateFiles(array $value): void
    {
        $this->generatorTemplateFiles = $value;
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    public function getCompact(): bool
    {
        return $this->compact;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function getGeneratorTemplateFiles(string $key): ?string
    {
        return $this->generatorTemplateFiles[$key] ?? null;
    }

    public function getMaxNameLength(): int
    {
        return $this->maxNameLength;
    }

    public function getMigrationNameLimit()
    {
        if ($this->migrationNameLimit !== null) {
            return $this->migrationNameLimit;
        }

        $tableSchema = $this->db->getSchema() ? $this->db->getSchema()->getTableSchema($this->migrationTable, true) : null;

        if ($tableSchema !== null) {
            return $this->migrationNameLimit = $tableSchema->getColumns()['version']->getSize();
        }

        return $this->maxNameLength;
    }

    public function getMigrationHistory(?int $limit = 0): array
    {
        if ($this->db->getSchema()->getTableSchema($this->migrationTable, true) === null) {
            $this->createMigrationHistoryTable();
        }

        $query = (new Query($this->db))
            ->select(['version', 'apply_time'])
            ->from($this->migrationTable)
            ->orderBy(['apply_time' => SORT_DESC, 'version' => SORT_DESC]);

        if (empty($this->migrationNamespaces)) {
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
        foreach ($rows as $key => $row) {
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

        usort($history, static function ($a, $b) {
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

    public function getMigrationNamespaces(): array
    {
        return $this->migrationNamespaces;
    }

    public function getMigrationPath(): array
    {
        return $this->migrationPath;
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
        if (is_array($this->migrationPath)) {
            foreach ($this->migrationPath as $path) {
                $migrationPaths[] = [$path, ''];
            }
        } elseif (!empty($this->migrationPath)) {
            $migrationPaths[] = [$this->migrationPath, ''];
        }

        foreach ($this->migrationNamespaces as $namespace) {
            $migrationPaths[] = [$this->getNamespacePath($namespace), $namespace];
        }

        $migrations = [];
        foreach ($migrationPaths as $item) {
            [$migrationPath, $namespace] = $item;
            if (!file_exists($migrationPath)) {
                continue;
            }
            $handle = opendir($migrationPath);
            while (($file = readdir($handle)) !== false) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
                $path = $migrationPath . DIRECTORY_SEPARATOR . $file;
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

    public function getTemplateFile(): string
    {
        return $this->templateFile;
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
     * List of namespaces containing the migration classes.
     *
     * @param array $value
     *
     * Migration namespaces should be resolvable as a [path alias](guide:concept-aliases) if prefixed with `@`, e.g.
     * if you specify the namespace `app\migrations`, the code `$this->aliases->get('@app/migrations')` should be able
     * to return the file path to the directory this namespace refers to.
     *
     * This corresponds with the [autoloading conventions](guide:concept-autoloading) of Yii.
     */
    public function migrationNamespaces(array $value): void
    {
        $this->migrationNamespaces = $value;
    }

    /**
     * The directory containing the migration classes.
     *
     * This can be either a [path alias](guide:concept-aliases) or a directory path.
     *
     * Migration classes located at this path should be declared without a namespace.
     * Use {@see migrationNamespaces} property in case you are using namespaced migrations.
     *
     * If you have set up {migrationNamespaces}, you may set this field to `null` in order to disable usage of
     * migrations that are not namespaced.
     *
     * In general, to load migrations from different locations, {migrationNamespaces} is the preferable solution as the
     * migration name contains the origin of the migration in the history, which is not the case when using multiple
     * migration paths.
     *
     * @param array $value
     *
     * {@see $migrationNamespaces}
     */
    public function migrationPath(array $value): void
    {
        $this->migrationPath = $value;
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
     * Set the value of templateFile
     *
     * @param string $value
     */
    public function templateFile(string $value): void
    {
        $this->templateFile = $value;
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
            "<fg=cyan>\nDriver: {$this->db->getDrivername()} - Version: {$this->db->getServerVersion()}. </><fg=white>Powered by: YiiFrameWork</>"
        );
    }

    public function addMigrationHistory($version): void
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
     * @param string $class the migration class name
     *
     * @return MigrationInterface the migration instance
     */
    public function createMigration(string $class): MigrationInterface
    {
        $this->includeMigrationFile($class);

        $migration = new $class($this->db);

        if ($migration instanceof MigrationInterface) {
            $migration->compact = $this->compact;
        }

        return $migration;
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

        $this->consoleHelper->output()->writeln("\t<fg=green>>>> [Ok] - Done.</>\n");
    }

    /**
     * Includes the migration file for a given migration class name.
     *
     * This function will do nothing on namespaced migrations, which are loaded by autoloading automatically. It will
     * include the migration file, by searching {@see migrationPath} for classes without namespace.
     *
     * @param string $class the migration class name.
     */
    private function includeMigrationFile(string $class): void
    {
        $class = trim($class, '\\');
        if (strpos($class, '\\') === false) {
            if (is_array($this->migrationPath)) {
                foreach ($this->migrationPath as $path) {
                    $file = $path . DIRECTORY_SEPARATOR . $class . '.php';
                    if (is_file($file)) {
                        require_once $file;
                        break;
                    }
                }
            } else {
                $file = $this->migrationPath . DIRECTORY_SEPARATOR . $class . '.php';
                require_once $file;
            }
        }
    }

    public function title(): void
    {
        $this->consoleHelper->io()->title("Yii Db Migration Tool Generator v-{$this->version}");
    }

    /**
     * Generates class base name and namespace from migration name from user input.
     *
     * @param string $name migration name from user input.
     *
     * @return array list of 2 elements: 'namespace' and 'class base name'
     */
    public function generateClassName(string $name): array
    {
        $namespace = null;
        $name = trim($name, '\\');
        if (strpos($name, '\\') !== false) {
            $namespace = substr($name, 0, strrpos($name, '\\'));
            $name = substr($name, strrpos($name, '\\') + 1);
        } elseif ($this->migrationPath === null) {
            $migrationNamespaces = $this->migrationNamespaces;
            $namespace = array_shift($migrationNamespaces);
        }

        if ($namespace === null) {
            $class = 'm' . gmdate('ymd_His') . '_' . $name;
        } else {
            $class = 'M' . gmdate('ymdHis') . Inflector::camelize($name);
        }

        return [$namespace, $class];
    }

    /**
     * Finds the file path for the specified migration namespace.
     *
     * @param string|null $namespace migration namespace.
     *
     * @return string migration file path.
     * @throws Exception on failure.
     */
    public function findMigrationPath(?string $namespace): string
    {
        if (empty($namespace)) {
            return is_array($this->migrationPath) ? reset($this->migrationPath) : $this->migrationPath;
        }

        if (!in_array($namespace, $this->migrationNamespaces, true)) {
            throw new Exception("Namespace '{$namespace}' not found in `migrationNamespaces`");
        }

        return $this->getNamespacePath($namespace);
    }

    /**
     * Normalizes table name for generator.
     *
     * When name is preceded with underscore name case is kept - otherwise it's converted from camelcase to underscored.
     * Last underscore is always trimmed so if there should be underscore at the end of name use two of them.
     *
     * @param string $name
     *
     * @return string
     */
    private function normalizeTableName(string $name): string
    {
        if (substr($name, -1) === '_') {
            $name = substr($name, 0, -1);
        }

        if (strpos($name, '_') === 0) {
            return substr($name, 1);
        }

        return $this->inflector->underscore($name);
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
     * Returns the file path matching the give namespace.
     *
     * @param string $namespace namespace.
     *
     * @return string file path.
     */
    private function getNamespacePath(string $namespace): string
    {
        return str_replace('/', DIRECTORY_SEPARATOR, $this->aliases->get('@' . str_replace('\\', '/', $namespace)));
    }
}
