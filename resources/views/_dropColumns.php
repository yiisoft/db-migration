<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\Service\Generate\Column;
use Yiisoft\Db\Migration\Service\Generate\ForeignKey;
use Yiisoft\Db\Migration\Service\Generate\PhpRenderer;

/**
 * @var PhpRenderer $this
 * @var string $table The table name.
 * @var Column[] $columns Fields.
 * @var ForeignKey[] $foreignKeys Foreign keys.
 */

echo $this->render(__DIR__ . '/_dropForeignKeys.php', [
    'table' => $table,
    'foreignKeys' => $foreignKeys,
]);

foreach ($columns as $column) {
    echo "        \$b->dropColumn('$table', '{$column->getName()}');\n";
}
