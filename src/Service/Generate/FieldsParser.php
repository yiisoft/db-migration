<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Service\Generate;

use function in_array;

/**
 * @internal
 */
final class FieldsParser
{
    public function __construct(private ForeignKeyFactory $foreignKeyFactory)
    {
    }

    /**
     * @return array[]
     * @psalm-return array{0:Column[],1:ForeignKey[]}
     */
    public function parse(
        string $table,
        ?string $value,
        bool $addDefaultPrimaryKey
    ): array {
        $columns = [];
        $foreignKeys = [];

        if (!empty($value)) {
            $fields = explode(',', $value);
            foreach ($fields as $field) {
                $chunks = $this->splitFieldIntoChunks($field);
                $property = array_shift($chunks);

                foreach ($chunks as $i => $chunk) {
                    if (str_starts_with($chunk, 'foreignKey')) {
                        preg_match('/foreignKey\((\w*)\s?(\w*)\)/', $chunk, $matches);
                        $foreignKeys[] = $this->foreignKeyFactory->create(
                            $table,
                            $property,
                            $matches[1] ?? preg_replace('/_id$/', '', $property),
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
}
