<?php

declare(strict_types=1);

/**
 * Creates a call for the method `Yiisoft\Yii\Db\Migration\MigrationBuilder::createTable()`.
 *
 * @var $this \Yiisoft\View\WebView
 * @var $table string the name table
 * @var $columns \Yiisoft\Yii\Db\Migration\Service\Generate\Column[] the fields
 * @var $foreignKeys array the foreign keys
 */

echo "        \$b->createTable('$table', [\n";
foreach ($columns as $column) {
    if (!$column->hasDecorators()) {
        echo "            '{$column->getProperty()}',\n";
    } else {
        echo "            '{$column->getProperty()}' => \$b->{$column->getDecoratorsString()},\n";
    }
}
echo "        ]);\n";
echo $this->render('_addForeignKeys', [
    'table' => $table,
    'foreignKeys' => $foreignKeys,
]);
