<?php

declare(strict_types=1);

/**
 * Creates a call for the method `Yiisoft\Yii\Db\Migration\MigrationBuilder::dropTable()`.
 *
 * @var $this \Yiisoft\Yii\Db\Migration\Service\Generate\PhpRenderer
 * @var $table string The table name.
 * @var $foreignKeys array Foreign keys.
 */

echo $this->render(__DIR__ . '/_dropForeignKeys.php', [
    'table' => $table,
    'foreignKeys' => $foreignKeys,
]);

echo "        \$b->dropTable('$table');\n";
