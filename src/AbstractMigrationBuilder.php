<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration;

use Yiisoft\Db\Schema\Column\ColumnBuilder;
use Yiisoft\Db\Schema\Column\ColumnSchemaInterface;
use Yiisoft\Db\Schema\SchemaInterface;

/**
 * AbstractMigrationBuilder contains shortcut methods to create instances of {@see ColumnSchemaInterface}.
 *
 * These can be used in database migrations to define database schema types using a PHP interface. This is useful to
 * define a schema in a DBMS independent way so that the application may run on different DBMS the same way.
 *
 * For example, you may use the following code inside your migration files:
 *
 * ```php
 * $this->createTable(
 *     'example_table',
 *     [
 *         'id' => ColumnBuilder::primaryKey(),
 *         'name' => ColumnBuilder::string(64)->notNull(),
 *         'type' => ColumnBuilder::integer()->notNull()->defaultValue(10),
 *         'description' => ColumnBuilder::text(),
 *         'rule_name' => ColumnBuilder::string(64),
 *         'data' => ColumnBuilder::text(),
 *         'created_at' => ColumnBuilder::datetime()->notNull(),
 *         'updated_at' => ColumnBuilder::datetime(),
 *     ],
 * );
 * ```
 */
abstract class AbstractMigrationBuilder
{
    public function __construct(private SchemaInterface $schema)
    {
    }

    /**
     * Creates a bigint column.
     *
     * @param int|null $length The column size or precision definition.
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnSchemaInterface The column instance which can be further customized.
     *
     * @deprecated Use {@see ColumnBuilder::bigint()} instead. Will be removed in 2.0.0.
     */
    public function bigInteger(int $length = null): ColumnSchemaInterface
    {
        return ColumnBuilder::bigint($length);
    }

    /**
     * Creates a big primary key column.
     *
     * @param int|null $length The column size or precision definition.
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnSchemaInterface The column instance which can be further customized.
     *
     * @deprecated Use {@see ColumnBuilder::bigPrimaryKey()} instead. Will be removed in 2.0.0.
     */
    public function bigPrimaryKey(int $length = null): ColumnSchemaInterface
    {
        return ColumnBuilder::bigPrimaryKey()->size($length);
    }

    /**
     * Creates a UUID primary key column.
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnSchemaInterface The column instance which can be further customized.
     *
     * @deprecated Use {@see ColumnBuilder::uuidPrimaryKey()} instead. Will be removed in 2.0.0.
     */
    public function uuidPrimaryKey(): ColumnSchemaInterface
    {
        return ColumnBuilder::uuidPrimaryKey();
    }

    /**
     * Creates a UUID primary key column with a sequence.
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnSchemaInterface The column instance which can be further customized.
     *
     * @deprecated Use {@see ColumnBuilder::uuidPrimaryKey()} instead. Will be removed in 2.0.0.
     */
    public function uuidPrimaryKeySequenced(): ColumnSchemaInterface
    {
        return ColumnBuilder::uuidPrimaryKey();
    }

    /**
     * Creates a UUID column.
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnSchemaInterface The column instance which can be further customized.
     *
     * @deprecated Use {@see ColumnBuilder::uuid()} instead. Will be removed in 2.0.0.
     */
    public function uuid(): ColumnSchemaInterface
    {
        return ColumnBuilder::uuid();
    }

    /**
     * Creates a binary column.
     *
     * @param int|null $length The column size or precision definition.
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnSchemaInterface The column instance which can be further customized.
     *
     * @deprecated Use {@see ColumnBuilder::binary()} instead. Will be removed in 2.0.0.
     */
    public function binary(int $length = null): ColumnSchemaInterface
    {
        return ColumnBuilder::binary($length);
    }

    /**
     * Creates a boolean column.
     *
     * @return ColumnSchemaInterface The column instance which can be further customized.
     *
     * @deprecated Use {@see ColumnBuilder::boolean()} instead. Will be removed in 2.0.0.
     */
    public function boolean(): ColumnSchemaInterface
    {
        return ColumnBuilder::boolean();
    }

    /**
     * Creates a char column.
     *
     * @param int|null $length the column size definition, i.e., the maximum string length.
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnSchemaInterface The column instance which can be further customized.
     *
     * @deprecated Use {@see ColumnBuilder::char()} instead. Will be removed in 2.0.0.
     */
    public function char(int $length = null): ColumnSchemaInterface
    {
        return ColumnBuilder::char($length);
    }

    /**
     * Creates a date column.
     *
     * @return ColumnSchemaInterface The column instance which can be further customized.
     *
     * @deprecated Use {@see ColumnBuilder::date()} instead. Will be removed in 2.0.0.
     */
    public function date(): ColumnSchemaInterface
    {
        return ColumnBuilder::date();
    }

    /**
     * Creates a datetime column.
     *
     * @param int|null $precision The column value precision. First parameter passed to the column type, e.g.
     * DATETIME(precision).
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnSchemaInterface The column instance which can be further customized.
     *
     * @deprecated Use {@see ColumnBuilder::datetime()} instead. Will be removed in 2.0.0.
     */
    public function dateTime(int $precision = null): ColumnSchemaInterface
    {
        return ColumnBuilder::datetime($precision);
    }

    /**
     * Creates a decimal column.
     *
     * @param int|null $precision The column value precision, which is usually the total number of digits.
     * First parameter passed to the column type, e.g., DECIMAL(precision, scale).
     *
     * This parameter will be ignored if not supported by the DBMS.
     * @param int|null $scale The column value scale, which is usually the number of digits after the decimal point.
     * Second parameter passed to the column type, e.g., DECIMAL(precision, scale).
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnSchemaInterface The column instance which can be further customized.
     *
     * @deprecated Use {@see ColumnBuilder::decimal()} instead. Will be removed in 2.0.0.
     */
    public function decimal(int $precision = null, int $scale = null): ColumnSchemaInterface
    {
        return ColumnBuilder::decimal($precision, $scale);
    }

    /**
     * Creates a double column.
     *
     * @param int|null $precision The column value precision. First parameter passed to the column type, e.g.
     * DOUBLE(precision).
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnSchemaInterface The column instance which can be further customized.
     *
     * @deprecated Use {@see ColumnBuilder::double()} instead. Will be removed in 2.0.0.
     */
    public function double(int $precision = null): ColumnSchemaInterface
    {
        return ColumnBuilder::double($precision);
    }

    /**
     * Creates a float column.
     *
     * @param int|null $precision The column value precision. First parameter passed to the column type, e.g.
     * FLOAT(precision).
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnSchemaInterface The column instance which can be further customized.
     *
     * @deprecated Use {@see ColumnBuilder::float()} instead. Will be removed in 2.0.0.
     */
    public function float(int $precision = null): ColumnSchemaInterface
    {
        return ColumnBuilder::float($precision);
    }

    /**
     * Creates an integer column.
     *
     * @param int|null $length The column size or precision definition.
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnSchemaInterface The column instance which can be further customized.
     *
     * @deprecated Use {@see ColumnBuilder::integer()} instead. Will be removed in 2.0.0.
     */
    public function integer(int $length = null): ColumnSchemaInterface
    {
        return ColumnBuilder::integer($length);
    }

    /**
     * Creates a JSON column.
     *
     * @return ColumnSchemaInterface The column instance which can be further customized.
     *
     * @deprecated Use {@see ColumnBuilder::json()} instead. Will be removed in 2.0.0.
     */
    public function json(): ColumnSchemaInterface
    {
        return ColumnBuilder::json();
    }

    /**
     * Creates a money column.
     *
     * @param int|null $precision The column value precision, which is usually the total number of digits. First
     * parameter passed to the column type, e.g., DECIMAL(precision, scale).
     *
     * This parameter will be ignored if not supported by the DBMS.
     * @param int|null $scale The column value scale, which is usually the number of digits after the decimal point.
     * Second parameter passed to the column type, e.g., DECIMAL(precision, scale).
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnSchemaInterface The column instance which can be further customized.
     *
     * @deprecated Use {@see ColumnBuilder::money()} instead. Will be removed in 2.0.0.
     */
    public function money(int $precision = null, int $scale = null): ColumnSchemaInterface
    {
        return ColumnBuilder::money($precision, $scale);
    }

    /**
     * Creates a primary key column.
     *
     * @param int|null $length The column size or precision definition.
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnSchemaInterface The column instance which can be further customized.
     *
     * @deprecated Use {@see ColumnBuilder::primaryKey()} instead. Will be removed in 2.0.0.
     */
    public function primaryKey(int $length = null): ColumnSchemaInterface
    {
        return ColumnBuilder::primaryKey()->size($length);
    }

    /**
     * Creates a smallint column.
     *
     * @param int|null $length The column size or precision definition.
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnSchemaInterface The column instance which can be further customized.
     *
     * @deprecated Use {@see ColumnBuilder::smallint()} instead. Will be removed in 2.0.0.
     */
    public function smallInteger(int $length = null): ColumnSchemaInterface
    {
        return ColumnBuilder::smallint($length);
    }

    /**
     * Creates a string column.
     *
     * @param int|null $length The column size definition, i.e., the maximum string length.
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnSchemaInterface The column instance which can be further customized.
     *
     * @deprecated Use {@see ColumnBuilder::string()} instead. Will be removed in 2.0.0.
     */
    public function string(int $length = null): ColumnSchemaInterface
    {
        return ColumnBuilder::string($length);
    }

    /**
     * Creates a text column.
     *
     * @return ColumnSchemaInterface The column instance which can be further customized.
     *
     * @deprecated Use {@see ColumnBuilder::text()} instead. Will be removed in 2.0.0.
     */
    public function text(): ColumnSchemaInterface
    {
        return ColumnBuilder::text();
    }

    /**
     * Creates a time column.
     *
     * @param int|null $precision The column value precision. First parameter passed to the column type, e.g.
     * TIME(precision).
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnSchemaInterface The column instance which can be further customized.
     *
     * @deprecated Use {@see ColumnBuilder::time()} instead. Will be removed in 2.0.0.
     */
    public function time(int $precision = null): ColumnSchemaInterface
    {
        return ColumnBuilder::time($precision);
    }

    /**
     * Creates a timestamp column.
     *
     * @param int|null $precision The column value precision. First parameter passed to the column type, e.g.
     * TIMESTAMP(precision).
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnSchemaInterface The column instance which can be further customized.
     *
     * @deprecated Use {@see ColumnBuilder::timestamp()} instead. Will be removed in 2.0.0.
     */
    public function timestamp(int $precision = null): ColumnSchemaInterface
    {
        return ColumnBuilder::timestamp($precision);
    }

    /**
     * Creates a tinyint column. If tinyint is not supported by the DBMS, smallint will be used.
     *
     * @param int|null $length The column size or precision definition.
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnSchemaInterface The column instance which can be further customized.
     *
     * @deprecated Use {@see ColumnBuilder::tinyint()} instead. Will be removed in 2.0.0.
     */
    public function tinyInteger(int $length = null): ColumnSchemaInterface
    {
        return ColumnBuilder::tinyint($length);
    }
}
