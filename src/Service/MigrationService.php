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
use Yiisoft\Strings\Inflector;
use Yiisoft\Db\Migration\MigrationInterface;
use Yiisoft\Db\Migration\Migrator;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

use function dirname;

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
        $this->migrator->setIO($io);
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
     */
    public function getNewMigrations(): array
    {
        $applied = [];

        foreach ($this->migrator->getHistory() as $class => $time) {
            $applied[trim((string) $class, '\\')] = true;
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
            [$updatePaths, $namespace] = $item;
            $updatePaths = $this->aliases->get($updatePaths);

            if (!file_exists($updatePaths)) {
                continue;
            }

            $handle = opendir($updatePaths);
            while (($file = readdir($handle)) !== false) {
                if ($file === '.' || $file === '..') {
                    continue;
                }

                $path = $updatePaths . DIRECTORY_SEPARATOR . $file;

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
            fn(string $class) => $this->makeMigration($class),
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
            fn(string $class) => $this->makeRevertibleMigration($class),
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
     * Generates class base name and namespace from migration name from user input.
     *
     * @param string|null $namespace Migration namespace from the user input.
     * @param string $name Migration name from the user input.
     *
     * @return array List of 2 elements: 'namespace' and 'class base name'.
     */
    public function generateClassName(?string $namespace, string $name): array
    {
        if (empty($this->createPath) && empty($namespace)) {
            $namespace = $this->createNamespace;
        }

        $class = 'M' . gmdate('ymdHis') . (new Inflector())->toPascalCase($name);

        return [$namespace, $class];
    }

    /**
     * Finds the file path for the specified migration namespace.
     *
     * @param string|null $namespace The migration namespace.
     *
     * @return string The migration file path.
     */
    public function findMigrationPath(?string $namespace): string
    {
        $namespace ??= $this->createNamespace;

        return empty($namespace)
            ? $this->aliases->get($this->createPath)
            : $this->getNamespacePath($namespace);
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

        /**
         * @psalm-var array<string, array<int, string>> $map
         */
        foreach ($map as $namespace => $directories) {
            foreach ($directories as $directory) {
                $namespacesPath[str_replace('\\', '/', trim($namespace, '\\'))] = $directory;
            }
        }

        /** @psalm-var array<string, string> $namespacesPath */
        return (new Aliases($namespacesPath))->get($path);
    }

    private function getVendorDir(): string
    {
        $class = new ReflectionClass(ClassLoader::class);
        return dirname($class->getFileName(), 2);
    }
}
