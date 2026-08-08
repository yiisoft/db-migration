<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
    ->disableComposerAutoloadPathScan()
    ->setFileExtensions(['php'])
    ->addPathToScan(__DIR__ . '/bin', isDev: false)
    ->addPathToScan(__DIR__ . '/config', isDev: false)
    ->addPathToScan(__DIR__ . '/resources', isDev: false)
    ->addPathToScan(__DIR__ . '/src', isDev: false)
    ->addPathToScan(__DIR__ . '/tests', isDev: true)
    // These test suites only run in CI after their DBMS driver package (yiisoft/db-mssql,
    // yiisoft/db-mysql, yiisoft/db-oracle, yiisoft/db-pgsql) is installed on demand for the matching
    // per-DBMS workflow (see mssql.yml, mysql.yml, mariadb.yml, oracle.yml, pgsql.yml); the drivers are
    // intentionally not declared in composer.json.
    ->ignoreErrorsOnPaths(
        [
            __DIR__ . '/tests/Driver/Mssql',
            __DIR__ . '/tests/Driver/Mysql',
            __DIR__ . '/tests/Driver/Oracle',
            __DIR__ . '/tests/Driver/Pgsql',
            __DIR__ . '/tests/Support/Factory/MssqlFactory.php',
            __DIR__ . '/tests/Support/Factory/MysqlFactory.php',
            __DIR__ . '/tests/Support/Factory/OracleFactory.php',
            __DIR__ . '/tests/Support/Factory/PostgreSqlFactory.php',
        ],
        [ErrorType::UNKNOWN_CLASS]
    );
