<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\Service\Generate\PhpRenderer;

/**
 * Creates a call for the method `Yiisoft\Db\Migration\MigrationBuilder::dropTable()`.
 *
 * @var PhpRenderer $this
 * @var string $table The table name.
 * @var array $foreignKeys Foreign keys.
 */

echo $this->render(__DIR__ . '/_dropForeignKeys.php', [
    'table' => $table,
    'foreignKeys' => $foreignKeys,
]);

echo "        \$b->dropTable('$table');\n";
