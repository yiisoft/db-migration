<?php

declare(strict_types=1);

/**
 * @var $this \Yiisoft\View\WebView
 * @var $table string the name table
 * @var $fields array the fields
 * @var $foreignKeys array the foreign keys
 */

echo $this->render('_dropForeignKeys', [
    'table' => $table,
    'foreignKeys' => $foreignKeys,
]);

foreach ($fields as $field) {
    echo "        \$b->dropColumn('$table', '{$field['property']}');\n";
}
