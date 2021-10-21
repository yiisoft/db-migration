<?php

declare(strict_types=1);

/**
 * @var $this \Yiisoft\View\WebView
 * @var $table string the name table
 * @var $foreignKeys \Yiisoft\Yii\Db\Migration\Service\Generate\ForeignKey[] the foreign keys
 */

foreach ($foreignKeys as $foreignKey) {
    echo "        // drops foreign key for table `{$foreignKey->getRelatedTable()}`\n";
    echo "        \$b->dropForeignKey(\n";
    echo "            '{$foreignKey->getForeignKeyName()}',\n";
    echo "            '$table'\n";
    echo "        );\n";
    echo "\n";
    echo "        // drops index for column `{$foreignKey->getColumn()}`\n";
    echo "        \$b->dropIndex(\n";
    echo "            '{$foreignKey->getIndexName()}',\n";
    echo "            '$table'\n";
    echo "        );\n";
    echo "\n";
}
