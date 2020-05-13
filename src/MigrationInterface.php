<?php

namespace Yiisoft\Yii\Db\Migration;

/**
 * The MigrationInterface defines the minimum set of methods to be implemented by a database migration.
 *
 * Each migration class should provide the {@see up()} method containing the logic for "upgrading" the database and the
 * {@see down()} method for the "downgrading" logic.
 */
interface MigrationInterface
{
    /**
     * This method contains the logic to be executed when applying this migration.
     *
     * This method differs from {@see up()} in that the DB logic implemented here will be enclosed within a DB
     * transaction.
     * Child classes may implement this method instead of {@see up()} if the DB logic needs to be within a transaction.
     *
     * Note: Not all DBMS support transactions. And some DB queries cannot be put into a transaction. For some examples,
     * please refer to [implicit commit](http://dev.mysql.com/doc/refman/5.7/en/implicit-commit.html).
     *
     * @return bool return a false value to indicate the migration fails and should not proceed further. All other
     * return values mean the migration succeeds.
     */
    public function safeUp();

    /**
     * This method contains the logic to be executed when removing this migration.
     *
     * This method differs from {@see down()} in that the DB logic implemented here will be enclosed within a DB
     * transaction.
     * Child classes may implement this method instead of {@see down()} if the DB logic needs to be within a
     * transaction.
     *
     * Note: Not all DBMS support transactions. And some DB queries cannot be put into a transaction. For some examples,
     * please refer to [implicit commit](http://dev.mysql.com/doc/refman/5.7/en/implicit-commit.html).
     *
     * @return bool return a false value to indicate the migration fails and should not proceed further. All other
     * return values mean the migration succeeds.
     */
    public function safeDown();
}
