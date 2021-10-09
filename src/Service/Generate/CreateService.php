<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Service\Generate;

use Psr\EventDispatcher\EventDispatcherInterface;
use ReflectionException;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\View\View;
use Yiisoft\Yii\Db\Migration\Service\MigrationService;

use function array_merge;
use function array_shift;
use function array_unshift;
use function count;
use function dirname;
use function implode;
use function in_array;
use function is_array;
use function preg_match;
use function preg_match_all;
use function preg_replace;
use function preg_split;
use function str_replace;
use function strncmp;
use function strripos;

final class CreateService
{
    private Aliases $aliases;
    private ConnectionInterface $db;
    private MigrationService $migrationService;
    private View $view;
    private ?SymfonyStyle $io = null;

    public function __construct(
        Aliases $aliases,
        ConnectionInterface $db,
        MigrationService $migrationService,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->aliases = $aliases;
        $this->db = $db;
        $this->migrationService = $migrationService;
        $this->view = new View(
            dirname(__DIR__, 3) . '/resources/views',
            $eventDispatcher,
        );
    }

    public function setIO(?SymfonyStyle $io): void
    {
        $this->io = $io;
        $this->migrationService->setIO($io);
    }

    public function run(
        string $command,
        string $templateFile,
        string $table,
        string $className,
        ?string $namespace = null,
        array $fields = [],
        ?string $and = null
    ): string {
        $parsedFields = $this->parseFields($fields);
        $fields = $parsedFields['fields'];

        $foreignKeys = $parsedFields['foreignKeys'];

        if (in_array($command, ['table', 'dropTable'], true)) {
            $fields = $this->addDefaultPrimaryKey($fields);
        }

        if ($command === 'junction') {
            [$foreignKeys, $table, $fields] = $this->addJunction($table, $and, $fields);
        }

        $foreignKeys = $this->addforeignKeys($table, $foreignKeys);

        return $this->view->renderFile(
            $this->aliases->get($templateFile),
            [
                'table' => $table,
                'className' => $className,
                'namespace' => $namespace,
                'fields' => $fields,
                'foreignKeys' => $foreignKeys,
            ]
        );
    }

    /**
     * Adds default primary key to fields list if there's no primary key specified.
     *
     * @param array $fields parsed fields
     *
     * @return array
     */
    private function addDefaultPrimaryKey(array $fields): array
    {
        foreach ($fields as $field) {
            if (false !== strripos($field['decorators'], 'primaryKey()')) {
                return [];
            }
        }

        array_unshift($fields, ['property' => 'id', 'decorators' => 'primaryKey()']);

        return $fields;
    }

    /**
     * Adds foreign key to fields list.
     *
     * @param string $table
     * @param array $foreignKeys
     *
     * @return array
     */
    private function addForeignKeys(string $table, array $foreignKeys): array
    {
        foreach ($foreignKeys as $column => $foreignKey) {
            $relatedColumn = $foreignKey['column'];
            $relatedTable = $foreignKey['table'];

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

            $foreignKeys[$column] = [
                'idx' => "idx-$table-$column",
                'fk' => "fk-$table-$column",
                'relatedTable' => $this->generateTableName($relatedTable),
                'relatedColumn' => $relatedColumn,
            ];
        }

        return $foreignKeys;
    }

    private function addJunction(string $name, ?string $and, array $fields): array
    {
        $foreignKeys = [];

        $fields = array_merge(
            [
                [
                    'property' => $name . '_id',
                    'decorators' => 'integer()',
                ],
                [
                    'property' => $and . '_id',
                    'decorators' => 'integer()',
                ],
            ],
            $fields,
            [
                [
                    'property' => 'PRIMARY KEY(' .
                        $name . '_id, ' .
                        $and . '_id)',
                ],
            ]
        );

        $foreignKeys[$name . '_id']['table'] = $name;
        $foreignKeys[$and . '_id']['table'] = $and;
        $foreignKeys[$name . '_id']['column'] = null;
        $foreignKeys[$and . '_id']['column'] = null;
        $table = $name . '_' . $and;

        return [$foreignKeys, $table, $fields];
    }

    /**
     * If `useTablePrefix` equals true, then the table name will contain the prefix format.
     *
     * @param string $tableName the table name to generate.
     *
     * @return string
     */
    private function generateTableName(string $tableName): string
    {
        if (!$this->migrationService->getUseTablePrefix()) {
            return $tableName;
        }

        return '{{%' . $tableName . '}}';
    }

    /**
     * Parse the command line migration fields.
     *
     * - fields: array, parsed fields
     * - foreignKeys: array, detected foreign keys
     *
     * @param array $value
     *
     * @return array parse result with following fields:
     */
    private function parseFields(array $value): array
    {
        $fields = [];
        $foreignKeys = [];

        foreach ($value as $field) {
            $chunks = $this->splitFieldIntoChunks($field);
            $property = array_shift($chunks);

            foreach ($chunks as $i => &$chunk) {
                if (strncmp($chunk, 'foreignKey', 10) === 0) {
                    preg_match('/foreignKey\((\w*)\s?(\w*)\)/', $chunk, $matches);
                    $foreignKeys[$property] = [
                        'table' => $matches[1] ?? preg_replace('/_id$/', '', $property),
                        'column' => !empty($matches[2])
                            ? $matches[2]
                            : null,
                    ];

                    unset($chunks[$i]);
                    continue;
                }

                if (!preg_match('/^(.+?)\(([^(]+)\)$/', $chunk)) {
                    $chunk .= '()';
                }
            }

            $fields[] = [
                'property' => $property,
                'decorators' => implode('->', $chunks),
            ];
        }

        return [
            'fields' => $fields,
            'foreignKeys' => $foreignKeys,
        ];
    }

    /**
     * Splits field into chunks
     *
     * @param string $field
     *
     * @return array
     */
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

        $chunks = preg_split('/\s?:\s?/', $field);

        if (is_array($chunks) && $hasDoubleQuotes) {
            foreach ($chunks as $key => $chunk) {
                $chunks[$key] = str_replace($defaultValue, $originalDefaultValue, $chunk);
            }
        }

        return (array) $chunks;
    }
}
