<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Service\Generate;

use Symfony\Component\Console\Style\SymfonyStyle;

use Yiisoft\Db\Connection\ConnectionInterface;

use function count;
use function in_array;

/**
 * @internal
 */
final class FieldsParser
{
    private ConnectionInterface $db;
    private ?SymfonyStyle $io;
    private bool $useTablePrefix;

    public function __construct(
        ConnectionInterface $db,
        ?SymfonyStyle $io,
        bool $useTablePrefix
    ) {
        $this->db = $db;
        $this->io = $io;
        $this->useTablePrefix = $useTablePrefix;
    }

    /**
     * @return array[]
     * @psalm-return array{0:Column[],1:ForeignKey[]}
     */
    public function parse(
        string $table,
        ?string $value,
        bool $addDefaultPrimaryKey,
        bool $addJunction,
        ?string $and
    ): array {
        $columns = [];
        $foreignKeys = [];

        if (!empty($value)) {
            $fields = explode(',', $value);
            foreach ($fields as $field) {
                $chunks = $this->splitFieldIntoChunks($field);
                $property = array_shift($chunks);

                foreach ($chunks as $i => $chunk) {
                    if (strncmp($chunk, 'foreignKey', 10) === 0) {
                        preg_match('/foreignKey\((\w*)\s?(\w*)\)/', $chunk, $matches);
                        $foreignKeys[] = $this->createForeignKey(
                            $table,
                            $matches[1] ?? preg_replace('/_id$/', '', $property),
                            $property,
                            empty($matches[2]) ? null : $matches[2]
                        );

                        unset($chunks[$i]);
                        continue;
                    }

                    if (!preg_match('/^(.+?)\(([^(]+)\)$/', $chunk)) {
                        $chunks[$i] .= '()';
                    }
                }

                $columns[] = new Column($property, $chunks);
            }
        }

        if ($addDefaultPrimaryKey) {
            $this->addDefaultPrimaryKey($columns);
        }

        if ($addJunction) {
            $this->addJunction($table, (string) $and, $columns, $foreignKeys);
        }

        return [$columns, $foreignKeys];
    }

    private function splitFieldIntoChunks(string $field): array
    {
        $defaultValue = '';
        $originalDefaultValue = '';
        $hasDoubleQuotes = false;

        preg_match_all('/defaultValue\(.*?:.*?\)/', $field, $matches);

        if (isset($matches[0][0])) {
            $hasDoubleQuotes = true;
            $originalDefaultValue = $matches[0][0];
            $defaultValue = str_replace(':', '{{colon}}', $originalDefaultValue);
            $field = str_replace($originalDefaultValue, $defaultValue, $field);
        }

        /** @var string[] $chunks */
        $chunks = preg_split('/\s?:\s?/', $field);

        if ($hasDoubleQuotes) {
            foreach ($chunks as $key => $chunk) {
                $chunks[$key] = str_replace($defaultValue, $originalDefaultValue, $chunk);
            }
        }

        return $chunks;
    }

    /**
     * Adds default primary key to columns list if there's no primary key specified.
     *
     * @param Column[] $columns
     */
    private function addDefaultPrimaryKey(array &$columns): void
    {
        foreach ($columns as $column) {
            if (in_array('primaryKey()', $column->getDecorators(), true)) {
                return;
            }
        }

        array_unshift(
            $columns,
            new Column('id', ['primaryKey()']),
        );
    }

    /**
     * @param Column[] $columns
     * @param ForeignKey[] $foreignKeys
     */
    private function addJunction(string $table, string $and, array &$columns, array &$foreignKeys): void
    {
        $columns = array_merge(
            [
                new Column($table . '_id', ['integer()']),
                new Column($and . '_id', ['integer()']),
            ],
            $columns,
            [
                new Column('PRIMARY KEY(' . $table . '_id, ' . $and . '_id)'),
            ],
        );

        $foreignKeys[] = $this->createForeignKey($table . '_' . $and, $table, $table . '_id', null);
        $foreignKeys[] = $this->createForeignKey($table . '_' . $and, $and, $and . '_id', null);
    }

    private function createForeignKey(
        string $table,
        string $relatedTable,
        string $column,
        ?string $relatedColumn
    ): ForeignKey {
        /**
         * We're trying to get it from table schema.
         *
         * {@see https://github.com/yiisoft/yii2/issues/12748}
         */
        if ($relatedColumn === null) {
            $relatedColumn = 'id';
            $relatedTableSchema = $this->db->getTableSchema($relatedTable);
            if ($relatedTableSchema !== null) {
                $primaryKeyCount = count($relatedTableSchema->getPrimaryKey());
                if ($primaryKeyCount === 1) {
                    $relatedColumn = $relatedTableSchema->getPrimaryKey()[0];
                } elseif ($primaryKeyCount > 1) {
                    if ($this->io) {
                        $this->io->writeln(
                            "<fg=yellow> Related table for field \"{$column}\" exists, but primary key is" .
                            "composite. Default name \"id\" will be used for related field</>\n"
                        );
                    }
                } elseif ($primaryKeyCount === 0) {
                    if ($this->io) {
                        $this->io->writeln(
                            "<fg=yellow>Related table for field \"{$column}\" exists, but does not have a " .
                            "primary key. Default name \"id\" will be used for related field.</>\n"
                        );
                    }
                }
            }
        }

        return new ForeignKey(
            "idx-$table-$column",
            "fk-$table-$column",
            $column,
            $this->useTablePrefix ? '{{%' . $relatedTable . '}}' : $relatedTable,
            $relatedColumn,
        );
    }
}
