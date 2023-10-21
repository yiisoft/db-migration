<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Service;

use Composer\Autoload\ClassLoader;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Injector\Injector;
use Yiisoft\Db\Migration\MigrationInterface;
use Yiisoft\Db\Migration\Migrator;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

use function array_map;
use function array_values;
use function closedir;
use function dirname;
use function gmdate;
use function is_dir;
use function is_file;
use function ksort;
use function opendir;
use function preg_match;
use function preg_replace;
use function readdir;
use function str_contains;
use function str_replace;
use function trim;
use function ucwords;

final class MigrationService
{
    private string $createNamespace = '';
    private string $createPath = '';
    /** @psalm-var string[] */
    private array $updateNamespaces = [];
    /** @psalm-var string[] */
    private array $updatePaths = [];
    private string $version = '1.0';
    private ?SymfonyStyle $io = null;

    public function __construct(
        private Aliases $aliases,
        private ConnectionInterface $db,
        private Injector $injector,
        private Migrator $migrator
    ) {
    }

    public function setIO(?SymfonyStyle $io): void
    {
        $this->io = $io;
    }

    /**
     * This method is invoked right before an action is to be executed (after all possible filters.)
     *
     * It checks the existence of the {@see createPath}, {@see updatePaths}, {@see createNamespace},
     * {@see updateNamespaces}.
     *
     * @return int Whether the action should continue to be executed.
     */
    public function before(string $defaultName): int
    {
        $result = Command::SUCCESS;

        switch ($defaultName) {
            case 'migrate:create':
                if (empty($this->createNamespace) && empty($this->createPath)) {
                    $this->io?->error(
                        'One of `createNamespace` or `createPath` should be specified.'
                    );

                    $result = Command::INVALID;
                }

                if (!empty($this->createNamespace) && !empty($this->createPath)) {
                    $this->io?->error(
                        'Only one of `createNamespace` or `createPath` should be specified.'
                    );

                    $result = Command::INVALID;
                }
                break;
            case 'migrate:up':
                if (empty($this->updateNamespaces) && empty($this->updatePaths)) {
                    $this->io?->error(
                        'At least one of `updateNamespaces` or `updatePaths` should be specified.'
                    );

                    $result = Command::INVALID;
                }
                break;
        }

        return $result;
    }

    /**
     * Returns the migrations that are not applied.
     *
     * @return array List of new migrations.
     *
     * @psalm-return array<int, class-string>
     */
    public function getNewMigrations(): array
    {
        $applied = [];

        foreach ($this->migrator->getHistory() as $class => $time) {
            $applied[trim($class, '\\')] = true;
        }

        $migrationPaths = [];

        foreach ($this->updatePaths as $path) {
            $migrationPaths[] = [$path, ''];
        }

        foreach ($this->updateNamespaces as $namespace) {
            $migrationPaths[] = [$this->getNamespacePath($namespace), $namespace];
        }

        $migrations = [];
        foreach ($migrationPaths as $item) {
            [$updatePath, $namespace] = $item;
            $updatePath = $this->aliases->get($updatePath);

            if (!is_dir($updatePath)) {
                continue;
            }

            $handle = opendir($updatePath);
            while (($file = readdir($handle)) !== false) {
                if ($file === '.' || $file === '..') {
                    continue;
                }

                $path = $updatePath . DIRECTORY_SEPARATOR . $file;

                if (is_file($path) && preg_match('/^(M(\d{12}).*)\.php$/s', $file, $matches)) {
                    [, $class, $time] = $matches;

                    if (!empty($namespace)) {
                        $class = $namespace . '\\' . $class;
                    }

                    if (!isset($applied[$class])) {
                        $migrations[$time . '\\' . $class] = $class;
                    }
                }
            }
            closedir($handle);
        }
        ksort($migrations);

        /** @psalm-var array<int, class-string> */
        return array_values($migrations);
    }

    /**
     * List of namespaces containing the migration update classes.
     *
     * Migration namespaces should be resolvable as a [path alias](guide:concept-aliases) if prefixed with `@`, e.g.
     * if you specify the namespace `app\migrations`, the code `$this->aliases->get('@app/migrations')` should be able
     * to return the file path to the directory this namespace refers to.
     * This corresponds with the [autoloading conventions](guide:concept-autoloading) of Yii.
     *
     * @psalm-param string[] $value
     */
    public function updateNamespaces(array $value): void
    {
        $this->updateNamespaces = $value;
    }

    /**
     * The directory containing the migration update classes.
     *
     * This can be either a [path alias](guide:concept-aliases) or a directory path.
     *
     * Migration classes located at this path should be declared without a namespace.
     * Use {@see createNamespace} property in case you are using namespaced migrations.
     *
     * If you have set up {@see createNamespace}, you may set this field to `null` in order to disable usage of  migrations
     * that are not namespaced.
     *
     * In general, to load migrations from different locations, {@see createNamespace} is the preferable solution as the
     * migration name contains the origin of the migration in the history, which is not the case when using multiple
     * migration paths.
     *
     *
     * {@see $createNamespace}
     *
     * @psalm-param string[] $value
     */
    public function updatePaths(array $value): void
    {
        $this->updatePaths = $value;
    }

    public function version(): string
    {
        return $this->version;
    }

    public function databaseConnection(): void
    {
        $this->io?->writeln(
            "<fg=cyan>\nDatabase connection: {$this->db->getDriverName()}.</>"
        );
    }

    /**
     * Creates a new migration instance.
     *
     * @psalm-suppress MoreSpecificReturnType
     * @psalm-suppress LessSpecificReturnStatement
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
            foreach ($this->updatePaths as $path) {
                $file = $this->aliases->get($path) . DIRECTORY_SEPARATOR . $class . '.php';

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
     * @psalm-param class-string[] $classes
     *
     * @return MigrationInterface[]
     */
    public function makeMigrations(array $classes): array
    {
        return array_map(
            [$this, 'makeMigration'],
            $classes
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
     * @psalm-param class-string[] $classes
     *
     * @return RevertibleMigrationInterface[]
     */
    public function makeRevertibleMigrations(array $classes): array
    {
        return array_map(
            [$this, 'makeRevertibleMigration'],
            $classes
        );
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
     * Returns namespace to create migration
     *
     * @return string
     */
    public function getCreateNamespace(): string
    {
        return $this->createNamespace;
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
        return 'M' . gmdate('ymdHis')
            . str_replace(' ', '', ucwords(preg_replace('/[^a-z0-9]+/i', ' ', $name)));
    }

    /**
     * Finds the file path for migration namespace or alias path.
     *
     * @return string The migration file path.
     */
    public function findMigrationPath(): string
    {
        return empty($this->createNamespace)
            ? $this->aliases->get($this->createPath)
            : $this->getNamespacePath($this->createNamespace);
    }

    /**
     * Returns the file path matching the give namespace.
     *
     * @param string $namespace Namespace.
     *
     * @return string File path.
     */
    private function getNamespacePath(string $namespace): string
    {
        $aliases = '@' . str_replace('\\', '/', $namespace);

        return $this->getPathFromNamespace($aliases);
    }

    private function getPathFromNamespace(string $path): string
    {
        $namespacesPath = [];

        /** @psalm-suppress UnresolvableInclude */
        $map = require $this->getVendorDir() . '/composer/autoload_psr4.php';

        /** @psalm-var array<string, array<int, string>> $map */
        foreach ($map as $namespace => $directories) {
            foreach ($directories as $directory) {
                $namespacesPath[str_replace('\\', '/', trim($namespace, '\\'))] = $directory;
            }
        }

        return (new Aliases($namespacesPath))->get($path);
    }

    private function getVendorDir(): string
    {
        $class = new ReflectionClass(ClassLoader::class);
        return dirname($class->getFileName(), 2);
    }
}
