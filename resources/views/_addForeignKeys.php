<?php

declare(strict_types=1);

/**
 * @var $this \Yiisoft\View\WebView
 * @var $table string
 * @var $foreignKeys array[]
 */

foreach ($foreignKeys as $column => $fkData) {
    echo "\n";
    echo "        // creates index for column `$column`\n";
    echo "        \$m->createIndex(\n";
    echo "            '{$fkData['idx']}',\n";
    echo "            '$table',\n";
    echo "            '$column'\n";
    echo "        );\n";
    echo "\n";
    echo "        // add foreign key for table `{$fkData['relatedTable']}`\n";
    echo "        \$m->addForeignKey(\n";
    echo "            '{$fkData['fk']}',\n";
    echo "            '$table',\n";
    echo "            '$column',\n";
    echo "            '{$fkData['relatedTable']}',\n";
    echo "            '{$fkData['relatedColumn']}',\n";
    echo "            'CASCADE'\n";
    echo "        );\n";
}
