<?php

namespace Yiisoft\Yii\Db\Migration;

/**
 * The MigrationInterface defines the minimum set of methods to be implemented by a database migration.
 *
 * Each migration class should provide the {@see up()} method containing the logic for "upgrading" the database.
 * If you need the migration to be revertible, use {@see RevertibleMigrationInterface}.
 */
interface MigrationInterface
{
    /**
     * This method contains the logic to be executed when applying this migration.
     *
     * The DB logic implemented here will be enclosed within a DB transaction.
     *
     * Child classes may implement this method instead of {@see up()} if the DB logic needs to be within a
     * transaction.
     *
     * Note: Not all DBMS support transactions. And some DB queries cannot be put into a transaction. For some examples,
     * please refer to [implicit commit](http://dev.mysql.com/doc/refman/5.7/en/implicit-commit.html).
     */
    public function up(): void;

    /**
     * @param bool $value indicates whether the console output should be compacted.
     *
     * If this is set to true, the individual commands ran within the migration will not be output to the console.
     *
     * Default is false, in other words the output is fully verbose by default.
     */
    public function compact(bool $value): void;
}
