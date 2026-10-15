<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration;

use InvalidArgumentException;
use Yiisoft\Db\Migration\Informer\MigrationInformerInterface;
use Yiisoft\Db\Migration\Service\MigrationService;
use Yiisoft\Injector\Injector;

use function array_keys;
use function get_debug_type;
use function is_int;
use function sprintf;
use function trim;

/**
 * Additional named databases, in execution order. The "default" database is always configured by the existing
 * command dependencies, so it can't be defined here.
 */
final class DatabaseRegistry
{
    /**
     * Reserved name of the default database configured by the existing command dependencies.
     */
    public const DEFAULT_DATABASE = 'default';

    /** @var array<string, DatabaseConfig> */
    private readonly array $configs;

    /**
     * @param array<array-key, DatabaseConfig> $databases Additional named databases, excluding "default".
     */
    public function __construct(
        private readonly Injector $injector,
        private readonly MigrationInformerInterface $informer,
        array $databases = [],
    ) {
        $configs = [];
        foreach ($databases as $name => $config) {
            if (is_int($name)) {
                throw new InvalidArgumentException(
                    sprintf('Database names must be non-numeric strings, "%s" given.', $name),
                );
            }
            if (trim($name) === '') {
                throw new InvalidArgumentException('Database names must be non-empty strings.');
            }
            if ($name === self::DEFAULT_DATABASE) {
                throw new InvalidArgumentException(
                    sprintf(
                        'The "%s" database is configured by the existing migration settings and can\'t be redefined.',
                        self::DEFAULT_DATABASE,
                    ),
                );
            }
            if (!$config instanceof DatabaseConfig) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Configuration of the "%s" database must be an instance of "%s", "%s" given.',
                        $name,
                        DatabaseConfig::class,
                        get_debug_type($config),
                    ),
                );
            }
            $configs[$name] = $config;
        }
        $this->configs = $configs;
    }

    /**
     * Returns names of additional databases, excluding "default".
     *
     * @return string[]
     *
     * @psalm-return list<string>
     */
    public function getNames(): array
    {
        return array_keys($this->configs);
    }

    /**
     * Creates a setup of an additional database: its configuration and migration services. Each call creates new
     * service instances.
     *
     * @throws InvalidArgumentException If the additional database isn't configured.
     *
     * @internal
     */
    public function createSetup(string $name): DatabaseSetup
    {
        $config = $this->configs[$name]
            ?? throw new InvalidArgumentException(sprintf('Unknown additional database: %s.', $name));
        $migrator = new Migrator(
            $config->db,
            $this->informer,
            $config->historyTable,
            $config->migrationNameLimit,
            $config->maxSqlOutputLength,
        );
        $service = new MigrationService($config->db, $this->injector, $migrator);
        $service->setNewMigrationNamespace($config->newMigrationNamespace);
        $service->setNewMigrationPath($config->newMigrationPath);
        $service->setSourceNamespaces($config->sourceNamespaces);
        $service->setSourcePaths($config->sourcePaths);

        return new DatabaseSetup($config, $migrator, $service);
    }
}
