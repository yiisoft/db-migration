<?php

declare(strict_types=1);

/**
 * Creates a call for the method `Yiisoft\Yii\Db\Migration\MigrationHelper::createTable()`.
 *
 * @var $this \Yiisoft\View\WebView
 * @var $table string the name table
 * @var $fields array the fields
 * @var $foreignKeys array the foreign keys
 */

echo "        \$m->createTable('$table', [\n";
foreach ($fields as $field) {
    if (empty($field['decorators'])) {
        echo "            '{$field['property']}',\n";
    } else {
        echo "            '{$field['property']}' => \$m->{$field['decorators']},\n";
    }
}
echo "        ]);\n";
echo $this->render('_addForeignKeys', [
    'table' => $table,
    'foreignKeys' => $foreignKeys,
]);
