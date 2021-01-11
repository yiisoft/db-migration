<?php

declare(strict_types=1);

/**
 * @var $this \Yiisoft\View\WebView
 * @var $table string the name table
 * @var $foreignKeys array the foreign keys
 */

foreach ($foreignKeys as $column => $fkData) {
    echo "        // drops foreign key for table `{$fkData['relatedTable']}`\n";
    echo "        \$b->dropForeignKey(\n";
    echo "            '{$fkData['fk']}',\n";
    echo "            '$table'\n";
    echo "        );\n";
    echo "\n";
    echo "        // drops index for column `$column`\n";
    echo "        \$b->dropIndex(\n";
    echo "            '{$fkData['idx']}',\n";
    echo "            '$table'\n";
    echo "        );\n";
    echo "\n";
}
