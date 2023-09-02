<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Service\Generate;

use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\InvalidConfigException;

use function dirname;
use function in_array;

final class CreateService
{
    private PhpRenderer $phpRenderer;
    private ?SymfonyStyle $io = null;

    /**
     * @psalm-var array<string,string>|null
     */
    private ?array $templates = null;

    /**
     * @param bool $useTablePrefix Indicates whether the table names generated should consider the `tablePrefix` setting
     * of the DB connection. For example, if the table name is `post` the generator will return `{{%post}}`.
     */
    public function __construct(
        private Aliases $aliases,
        private ConnectionInterface $db,
        private bool $useTablePrefix = true
    ) {
        $this->phpRenderer = new PhpRenderer();
    }

    public function run(
        string $command,
        string $table,
        string $className,
        string|null $namespace = null,
        string|null $fields = null,
        string|null $and = null,
        string|null $tableComment = null
    ): string {
        $templateFile = $this->getTemplate($command);

        $foreignKeyFactory = new ForeignKeyFactory(
            $this->db,
            $this->io,
            $this->useTablePrefix,
        );

        [$columns, $foreignKeys] = (new FieldsParser($foreignKeyFactory))->parse(
            $table,
            $fields,
            in_array($command, ['table', 'dropTable'], true)
        );

        if ($command === 'junction') {
            $and = (string) $and;
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

            $foreignKeys[] = $foreignKeyFactory->create($table . '_' . $and, $table . '_id', $table, null);
            $foreignKeys[] = $foreignKeyFactory->create($table . '_' . $and, $and . '_id', $and, null);

            $table .= '_' . $and;
        }

        return $this->phpRenderer->render(
            $this->aliases->get($templateFile),
            [
                'table' => $table,
                'className' => $className,
                'namespace' => $namespace,
                'columns' => $columns,
                'foreignKeys' => $foreignKeys,
                'tableComment' => $tableComment,
            ]
        );
    }

    public function getTemplate(?string $key): string
    {
        if ($this->templates === null) {
            $this->setDefaultTemplates();
        }

        if (!isset($this->templates[$key])) {
            throw new InvalidConfigException('You must define a template to generate the migration.');
        }

        return $this->templates[$key];
    }

    /**
     * Set of template paths for generating migration code automatically.
     *
     * The key is the template type, the value is a path or the alias.
     *
     * Supported types are:
     *
     * ```php
     *   'create' => '@yiisoft/yii/db/migration/resources/views/migration.php',
     *   'table' => '@yiisoft/yii/db/migration/resources/views/createTableMigration.php',
     *   'dropTable' => '@yiisoft/yii/db/migration/resources/views/dropTableMigration.php',
     *   'addColumn' => '@yiisoft/yii/db/migration/resources/views/addColumnMigration.php',
     *   'dropColumn' => '@yiisoft/yii/db/migration/resources/views/dropColumnMigration.php',
     *   'junction' => '@yiisoft/yii/db/migration/resources/views/createTableMigration.php'
     *```
     */
    public function setTemplate(string $key, string $value): void
    {
        $this->templates[$key] = $value;
    }

    /**
     * @psalm-param array<string,string> $value
     */
    public function setTemplates(array $value = []): void
    {
        $this->templates = $value;
    }

    public function setIO(?SymfonyStyle $io): void
    {
        $this->io = $io;
    }

    /**
     * @psalm-assert array<string,string> $this->templates
     */
    private function setDefaultTemplates(): void
    {
        $baseDir = dirname(__DIR__, 3) . '/resources/views';
        $this->templates = [
            'create' => $baseDir . '/migration.php',
            'table' => $baseDir . '/createTableMigration.php',
            'dropTable' => $baseDir . '/dropTableMigration.php',
            'addColumn' => $baseDir . '/addColumnMigration.php',
            'dropColumn' => $baseDir . '/dropColumnMigration.php',
            'junction' => $baseDir . '/createTableMigration.php',
        ];
    }
}
