<?php

declare(strict_types=1);

/**
 * @var $this \Yiisoft\View\WebView
 * @var $table string
 * @var $foreignKeys \Yiisoft\Yii\Db\Migration\Service\Generate\ForeignKey[]
 */

foreach ($foreignKeys as $foreignKey) {
    echo "\n";
    echo "        // creates index for column `{$foreignKey->getColumn()}`\n";
    echo "        \$b->createIndex(\n";
    echo "            '{$foreignKey->getIndexName()}',\n";
    echo "            '$table',\n";
    echo "            '{$foreignKey->getColumn()}'\n";
    echo "        );\n";
    echo "\n";
    echo "        // add foreign key for table `{$foreignKey->getRelatedTable()}`\n";
    echo "        \$b->addForeignKey(\n";
    echo "            '{$foreignKey->getForeignKeyName()}',\n";
    echo "            '$table',\n";
    echo "            '{$foreignKey->getColumn()}',\n";
    echo "            '{$foreignKey->getRelatedTable()}',\n";
    echo "            '{$foreignKey->getRelatedColumn()}',\n";
    echo "            'CASCADE'\n";
    echo "        );\n";
}
