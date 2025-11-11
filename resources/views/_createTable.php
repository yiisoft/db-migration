<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\Service\Generate\Column;
use Yiisoft\Db\Migration\Service\Generate\PhpRenderer;

/**
 * Creates a call for the method `Yiisoft\Db\Migration\MigrationBuilder::createTable()`.
 *
 * @var PhpRenderer $this
 * @var string $table The table name.
 * @var Column[] $columns Fields.
 * @var array $foreignKeys Foreign keys.
 */

echo "        \$columnBuilder = \$b->columnBuilder();\n\n";
echo "        \$b->createTable('$table', [\n";
foreach ($columns as $column) {
    if (!$column->hasDecorators()) {
        echo "            '{$column->getName()}',\n";
    } else {
        echo "            '{$column->getName()}' => \$columnBuilder::{$column->getDecoratorsString()},\n";
    }
}
echo "        ]);\n";
echo $this->render(__DIR__ . '/_addForeignKeys.php', [
    'table' => $table,
    'foreignKeys' => $foreignKeys,
]);
