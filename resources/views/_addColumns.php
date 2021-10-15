<?php

declare(strict_types=1);

/**
 * @var $this \Yiisoft\View\WebView
 * @var $table string
 * @var $columns \Yiisoft\Yii\Db\Migration\Service\Generate\Column[] the fields
 * @var $foreignKeys \Yiisoft\Yii\Db\Migration\Service\Generate\ForeignKey[] the foreign keys
 */

foreach ($columns as $column) {
    echo "        \$b->addColumn('$table', '{$column->getProperty()}', \$b->{$column->getDecoratorsString()});\n";
}

echo $this->render('_addForeignKeys', [
    'table' => $table,
    'foreignKeys' => $foreignKeys,
]);
