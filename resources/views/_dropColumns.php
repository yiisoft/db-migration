<?php

declare(strict_types=1);

/**
 * @var $this \Yiisoft\Yii\Db\Migration\Service\Generate\PhpRenderer
 * @var $table string The table name.
 * @var $columns \Yiisoft\Yii\Db\Migration\Service\Generate\Column[] Fields.
 * @var $foreignKeys \Yiisoft\Yii\Db\Migration\Service\Generate\ForeignKey[] Foreign keys.
 */

echo $this->render(__DIR__ . '/_dropForeignKeys.php', [
    'table' => $table,
    'foreignKeys' => $foreignKeys,
]);

foreach ($columns as $column) {
    echo "        \$b->dropColumn('$table', '{$column->getProperty()}');\n";
}
