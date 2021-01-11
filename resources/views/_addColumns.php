<?php

declare(strict_types=1);

/**
 * @var $this \Yiisoft\View\WebView
 * @var $table string
 * @var $fields array[]
 * @var $foreignKeys array
 */

foreach ($fields as $field) {
    echo "        \$b->addColumn('$table', '{$field['property']}', \$b->{$field['decorators']});\n";
}

echo $this->render('_addForeignKeys', [
    'table' => $table,
    'foreignKeys' => $foreignKeys,
]);
