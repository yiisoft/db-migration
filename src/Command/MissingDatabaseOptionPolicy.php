<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Command;

/**
 * Defines how a command behaves when the `--db` option isn't specified.
 *
 * @internal
 */
enum MissingDatabaseOptionPolicy
{
    /**
     * Process the default database only.
     */
    case ProcessDefaultDatabase;

    /**
     * Process all configured databases.
     */
    case ProcessAllDatabases;

    /**
     * Reject the command when multiple databases are configured. With a single database, it is processed.
     */
    case RejectIfMultiple;
}
