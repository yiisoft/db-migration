<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Service;

use Composer\Autoload\ClassLoader;
use LogicException;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Injector\Injector;
use Yiisoft\Db\Migration\MigrationInterface;
use Yiisoft\Db\Migration\Migrator;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

use function array_keys;
use function array_map;
use function array_merge;
use function array_unique;
use function array_values;
use function class_exists;
use function closedir;
use function dirname;
use function gmdate;
use function in_array;
use function is_dir;
use function is_file;
use function krsort;
use function ksort;
use function opendir;
use function preg_match;
use function preg_replace;
use function readdir;
use function realpath;
use function reset;
use function rtrim;
use function str_contains;
use function str_replace;
use function str_starts_with;
use function strlen;
use function strrchr;
use function strrpos;
use function substr;
use function trim;
use function ucwords;

use const DIRECTORY_SEPARATOR;

final class MigrationService
{
    private string $newMigrationNamespace = '';
    private string $newMigrationPath = '';
    /** @var string[] */
    private array $sourceNamespaces = [];
    /** @var string[] */
    private array $sourcePaths = [];
    private string $version = '1.0';
    private ?SymfonyStyle $io = null;

    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly Injector $injector,
        private readonly Migrator $migrator,
    ) {}

    public function setIo(?SymfonyStyle $io): void
    {
        $this->io = $io;
    }

    /**
     * This method is invoked right before an action is to be executed (after all possible filters.)
     *
     * It checks the existence of the {@see $newMigrationPath}, {@see $sourcePaths}, {@see $newMigrationNamespace},
     * {@see $sourceNamespaces}.
     *
     * @return int Whether the action should continue to be executed.
     */
    public function before(string $commandName): int
    {
        switch ($commandName) {
            case 'migrate:create':
                if (empty($this->newMigrationNamespace) && empty($this->newMigrationPath)) {
                    $this->io?->error(
                        'One of `newMigrationNamespace` or `newMigrationPath` should be specified.',
                    );

                    return Command::INVALID;
                }

                if (!empty($this->newMigrationNamespace) && !empty($this->newMigrationPath)) {
                    $this->io?->error(
                        'Only one of `newMigrationNamespace` or `newMigrationPath` should be specified.',
                    );

                    return Command::INVALID;
                }
                break;
            case 'migrate:up':
                if (empty($this->sourceNamespaces)
                    && empty($this->sourcePaths)
                    && empty($this->newMigrationNamespace)
                    && empty($this->newMigrationPath)
                ) {
                    $this->io?->error(
                        'At least one of `sourceNamespaces`, `sourcePaths`, `newMigrationNamespace` or `newMigrationPath` should be specified.',
                    );

                    return Command::INVALID;
                }
                break;
        }

        return Command::SUCCESS;
    }

    /**
     * Returns the migrations that are not applied.
     *
     * @return string[] List of new migrations.
     *
     * @psalm-return list<class-string>
     */
    public function getNewMigrations(): array
    {
        $applied = [];

        foreach ($this->migrator->getHistory() as $class => $time) {
            $applied[trim($class, '\\')] = true;
        }

        $migrations = $this->loadMigrationClasses();
        $migrations = array_filter($migrations, static fn(string $class): bool => !isset($applied[$class]));
        ksort($migrations);
        return array_values($migrations);
    }

    /**
     * List of namespaces containing the migration update classes.
     *
     * @psalm-param string[] $value
     */
    public function setSourceNamespaces(array $value): void
    {
        $this->sourceNamespaces = $value;
    }

    /**
     * The directory containing the migration update classes.
     *
     * Migration classes located on this path should be declared without a namespace.
     * Use {@see $newMigrationNamespace} property in case you are using namespaced migrations.
     *
     * If you have set up {@see $newMigrationNamespace}, you may set this field to `null` to disable usage of migrations
     * that are not namespaced.
     *
     * In general, to load migrations from different locations, {@see $newMigrationNamespace} is the preferable solution as the
     * migration name contains the origin of the migration in the history, which is not the case when using multiple
     * migration paths.
     *
     *
     * {@see $newMigrationNamespace}
     *
     * @psalm-param string[] $value
     */
    public function setSourcePaths(array $value): void
    {
        $this->sourcePaths = $value;
    }

    public function version(): string
    {
        return $this->version;
    }

    public function databaseConnection(): void
    {
        $this->io?->writeln(
            "<fg=cyan>Database connection: {$this->db->getDriverName()}.</>",
        );
    }

    public function makeMigration(string $class): MigrationInterface
    {
        $migration = $this->makeMigrationInstance($class);

        if (!$migration instanceof MigrationInterface) {
            throw new RuntimeException("Migration $class does not implement MigrationInterface.");
        }

        return $migration;
    }

    /**
     * @param string[] $classes
     *
     * @psalm-param list<class-string> $classes
     *
     * @return MigrationInterface[]
     *
     * @psalm-return list<MigrationInterface>
     */
    public function makeMigrations(array $classes): array
    {
        return array_map(
            $this->makeMigration(...),
            $classes,
        );
    }

    public function makeRevertibleMigration(string $class): RevertibleMigrationInterface
    {
        $migration = $this->makeMigrationInstance($class);

        if (!$migration instanceof RevertibleMigrationInterface) {
            throw new RuntimeException("Migration $class does not implement RevertibleMigrationInterface.");
        }

        return $migration;
    }

    /**
     * @param string[] $classes
     *
     * @psalm-param list<class-string> $classes
     *
     * @return RevertibleMigrationInterface[]
     *
     * @psalm-return list<RevertibleMigrationInterface>
     */
    public function makeRevertibleMigrations(array $classes): array
    {
        return array_map(
            $this->makeRevertibleMigration(...),
            $classes,
        );
    }

    public function setNewMigrationNamespace(string $value): void
    {
        $this->newMigrationNamespace = $value;
    }

    public function setNewMigrationPath(string $value): void
    {
        $this->newMigrationPath = $value;
    }

    /**
     * Returns namespace to create migration
     *
     * @return string
     */
    public function getNewMigrationNamespace(): string
    {
        return $this->newMigrationNamespace;
    }

    /**
     * Generates class base name from migration name from user input.
     *
     * @param string $name Migration name from the user input.
     *
     * @return string The class base name.
     */
    public function generateClassName(string $name): string
    {
        /** @var string $words */
        $words = preg_replace('/[^a-z0-9]+/i', ' ', $name);
        return 'M' . gmdate('ymdHis') . str_replace(' ', '', ucwords($words));
    }

    /**
     * Finds the file path for migration namespace or path.
     *
     * @return string The migration file path.
     */
    public function findMigrationPath(): string
    {
        return empty($this->newMigrationNamespace)
            ? $this->newMigrationPath
            : $this->getNamespacePath($this->newMigrationNamespace);
    }

    /**
     * Filters migrations by namespaces and paths.
     *
     * @param string[] $classes Migration classes to be filtered.
     * @param string[] $namespaces Namespaces to filter by.
     * @param string[] $paths Paths to filter by.
     *
     * @return string[] Filtered migration classes.
     *
     * @psalm-param list<class-string> $classes
     *
     * @psalm-return list<class-string>
     */
    public function filterMigrations(array $classes, array $namespaces = [], array $paths = []): array
    {
        $result = [];
        $pathNamespaces = [];

        foreach ($paths as $path) {
            $pathNamespaceList = $this->getNamespacesFromPath($path);

            if (!empty($pathNamespaceList)) {
                $pathNamespaces[$path] = $pathNamespaceList;
            }
        }

        $namespaces = array_map(static fn($namespace) => trim($namespace, '\\'), $namespaces);
        $namespaces = array_unique($namespaces);

        foreach ($classes as $class) {
            $classNamespace = substr($class, 0, strrpos($class, '\\') ?: 0);

            if ($classNamespace === '') {
                continue;
            }

            if (in_array($classNamespace, $namespaces, true)) {
                $result[] = $class;
                continue;
            }

            foreach ($pathNamespaces as $path => $pathNamespaceList) {
                /** @psalm-suppress RedundantCondition */
                if (!in_array($classNamespace, $pathNamespaceList, true)) {
                    continue;
                }

                /** @var string $className */
                $className = strrchr($class, '\\');
                $className = substr($className, 1);
                $file = $path . DIRECTORY_SEPARATOR . $className . '.php';

                if (is_file($file)) {
                    $result[] = $class;
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * Creates a new migration instance.
     *
     * @param string $class The migration class name.
     *
     * @return object The migration instance.
     */
    private function makeMigrationInstance(string $class): object
    {
        $class = trim($class, '\\');

        if (!str_contains($class, '\\')) {
            $isIncluded = false;

            $sourcePaths = $this->newMigrationPath !== ''
                ? [$this->newMigrationPath, ...$this->sourcePaths]
                : $this->sourcePaths;

            foreach ($sourcePaths as $path) {
                $file = $path . DIRECTORY_SEPARATOR . $class . '.php';

                if (is_file($file)) {
                    /** @psalm-suppress UnresolvableInclude */
                    require_once $file;
                    $isIncluded = true;
                    break;
                }
            }

            if (!$isIncluded) {
                throw new RuntimeException('Migration file not found.');
            }
        }

        /** @var class-string $class */
        $class = '\\' . $class;

        return $this->injector->make($class);
    }

    /**
     * Returns the migration paths with namespaces.
     *
     * @return true[][]
     * @psalm-return array<string, array<string, true>>
     */
    private function findSourcePaths(): array
    {
        $paths = [];

        if ($this->newMigrationPath !== '') {
            $newMigrationPath = $this->normalizePath($this->newMigrationPath);
            $newMigrationNamespaces = $this->getNamespacesFromPath($newMigrationPath);
            $paths[$newMigrationPath] = array_fill_keys($newMigrationNamespaces, true);
        } elseif ($this->newMigrationNamespace !== '') {
            $newMigrationPath = $this->getNamespacePath($this->newMigrationNamespace);
            $paths[$newMigrationPath][$this->newMigrationNamespace] = true;
        }

        foreach ($this->sourcePaths as $sourcePath) {
            $sourcePath = $this->normalizePath($sourcePath);
            $sourceNamespaces = $this->getNamespacesFromPath($sourcePath);
            $paths[$sourcePath] = ($paths[$sourcePath] ?? []) + array_fill_keys($sourceNamespaces, true);
        }

        foreach ($this->sourceNamespaces as $sourceNamespace) {
            $sourcePath = $this->getNamespacePath($sourceNamespace);
            $paths[$sourcePath][$sourceNamespace] = true;
        }

        return $paths;
    }

    /**
     * Returns the file path matching the give namespace.
     *
     * @param string $namespace Namespace.
     *
     * @throws LogicException If the namespace is invalid.
     *
     * @return string File path.
     */
    private function getNamespacePath(string $namespace): string
    {
        /**
         * @psalm-suppress UnresolvableInclude
         * @psalm-var array<string, list<string>> $map
         */
        $map = require $this->getVendorDir() . '/composer/autoload_psr4.php';

        foreach ($map as $mapNamespace => $mapDirectories) {
            if (str_starts_with($namespace, trim($mapNamespace, '\\'))) {
                /** @var string $mapDirectory */
                $mapDirectory = reset($mapDirectories);
                $path = $mapDirectory . '/' . str_replace('\\', '/', substr($namespace, strlen($mapNamespace)));

                if (is_dir($path)) {
                    return $this->normalizePath($path);
                }
            }
        }

        throw new LogicException("Invalid namespace \"$namespace\"");
    }

    /**
     * Returns the namespaces matching the give file path.
     *
     * @param string $path File path.
     *
     * @return string[] Namespaces.
     * @psalm-return list<string>
     */
    private function getNamespacesFromPath(string $path): array
    {
        $namespaces = [];
        /** @var string $path */
        $path = realpath($path);
        $path .= DIRECTORY_SEPARATOR;

        /**
         * @psalm-suppress UnresolvableInclude
         * @psalm-var array<string, list<string>> $map
         */
        $map = require $this->getVendorDir() . '/composer/autoload_psr4.php';

        foreach ($map as $namespace => $directories) {
            foreach ($directories as $directory) {
                if (!is_dir($directory)) {
                    continue;
                }

                /** @var string $directory */
                $directory = realpath($directory);
                $directory .= DIRECTORY_SEPARATOR;

                if (str_starts_with($path, $directory)) {
                    $length = strlen($directory);
                    $pathNamespace = $namespace . str_replace('/', '\\', substr($path, $length));
                    $namespaces[$length][$namespace] = rtrim($pathNamespace, '\\');
                }
            }
        }

        if (empty($namespaces)) {
            return [];
        }

        krsort($namespaces);

        /** @psalm-var list<string> */
        return array_values(array_unique(array_merge(...$namespaces)));
    }

    private function getVendorDir(): string
    {
        $class = new ReflectionClass(ClassLoader::class);
        return dirname($class->getFileName(), 2);
    }

    /**
     * Loads migration classes.
     *
     * @return string[] List of migration classes indexed by their time stamp and file path.
     * @psalm-return class-string[]
     */
    private function loadMigrationClasses(): array
    {
        $migrationPaths = $this->findSourcePaths();

        $migrations = [];

        foreach ($migrationPaths as $path => $namespaces) {
            /** @var resource $handle */
            $handle = opendir($path);
            while (($file = readdir($handle)) !== false) {
                if ($file === '.' || $file === '..') {
                    continue;
                }

                $filePath = "$path/$file";

                if (!is_file($filePath)) {
                    continue;
                }

                if (preg_match('/^(M(\d{12}).*)\.php$/s', $file, $matches) !== 1) {
                    continue;
                }

                [, $class, $time] = $matches;

                $sortKey = "$time/$filePath";

                if (isset($migrations[$sortKey])) {
                    continue;
                }

                require_once $filePath;

                foreach (array_keys($namespaces) as $namespace) {
                    if (class_exists($namespace . '\\' . $class, false)) {
                        /** @psalm-var class-string */
                        $migrations[$sortKey] = $namespace . '\\' . $class;
                        break;
                    }

                    if (class_exists($class, false)) {
                        /** @psalm-var class-string $class */
                        $migrations[$sortKey] = $class;
                        break;
                    }
                }
            }
            closedir($handle);
        }

        return $migrations;
    }

    private function normalizePath(string $path): string
    {
        if (!is_dir($path)) {
            throw new LogicException("Invalid path directory \"$path\"");
        }

        return rtrim(str_replace('\\', '/', $path), '/');
    }
}
