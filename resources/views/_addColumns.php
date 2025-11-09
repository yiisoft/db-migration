<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\Service\Generate\Column;
use Yiisoft\Db\Migration\Service\Generate\ForeignKey;
use Yiisoft\Db\Migration\Service\Generate\PhpRenderer;

/**
 * @var PhpRenderer $this
 * @var string $table
 * @var Column[] $columns Fields.
 * @var ForeignKey[] $foreignKeys Foreign keys.
 */

foreach ($columns as $column) {
    echo "        \$b->addColumn('$table', '{$column->getName()}', \$b->{$column->getDecoratorsString()});\n";
}

echo $this->render(__DIR__ . '/_addForeignKeys.php', [
    'table' => $table,
    'foreignKeys' => $foreignKeys,
]);
