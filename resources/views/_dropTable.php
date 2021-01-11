<?php

declare(strict_types=1);

/**
 * Creates a call for the method `Yiisoft\Yii\Db\Migration\MigrationBuilder::dropTable()`.
 *
 * @var $this \Yiisoft\View\WebView
 * @var $table string the name table
 * @var $foreignKeys array the foreign keys
 */

echo $this->render('_dropForeignKeys', [
    'table' => $table,
    'foreignKeys' => $foreignKeys,
]);

echo "        \$b->dropTable('$table');\n";
