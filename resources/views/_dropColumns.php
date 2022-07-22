<?php

declare(strict_types=1);

/**
 * @var $this \Yiisoft\Yii\Db\Migration\Service\Generate\PhpRenderer
 * @var $table string the name table
 * @var $columns \Yiisoft\Yii\Db\Migration\Service\Generate\Column[] the fields
 * @var $foreignKeys \Yiisoft\Yii\Db\Migration\Service\Generate\ForeignKey[] the foreign keys
 */

echo $this->render(__DIR__ . './_dropForeignKeys.php', [
    'table' => $table,
    'foreignKeys' => $foreignKeys,
]);

foreach ($columns as $column) {
    echo "        \$b->dropColumn('$table', '{$column->getProperty()}');\n";
}
