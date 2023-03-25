<?php

declare(strict_types=1);

/**
 * @var $this \Yiisoft\Yii\Db\Migration\Service\Generate\PhpRenderer
 * @var $table string the name table
 * @var $foreignKeys \Yiisoft\Yii\Db\Migration\Service\Generate\ForeignKey[] the foreign keys
 */

foreach ($foreignKeys as $foreignKey) {
    echo "        // drops foreign key for table `{$foreignKey->getRelatedTable()}`\n";
    echo "        \$b->dropForeignKey(\n";
    echo "            '$table',\n";
    echo "            '{$foreignKey->getForeignKeyName()}'\n";
    echo "        );\n";
    echo "\n";
    echo "        // drops index for column `{$foreignKey->getColumn()}`\n";
    echo "        \$b->dropIndex(\n";
    echo "            '$table',\n";
    echo "            '{$foreignKey->getIndexName()}'\n";
    echo "        );\n";
    echo "\n";
}
