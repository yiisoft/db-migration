<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\Service\Generate\ForeignKey;
use Yiisoft\Db\Migration\Service\Generate\PhpRenderer;

/**
 * @var PhpRenderer $this
 * @var string $table
 * @var ForeignKey[] $foreignKeys
 */

foreach ($foreignKeys as $foreignKey) {
    echo "\n";
    echo "        // creates index for column `{$foreignKey->getColumn()}`\n";
    echo "        \$b->createIndex(\n";
    echo "            '$table',\n";
    echo "            '{$foreignKey->getIndexName()}',\n";
    echo "            '{$foreignKey->getColumn()}'\n";
    echo "        );\n";
    echo "\n";
    echo "        // add foreign key for table `{$foreignKey->getRelatedTable()}`\n";
    echo "        \$b->addForeignKey(\n";
    echo "            '$table',\n";
    echo "            '{$foreignKey->getForeignKeyName()}',\n";
    echo "            '{$foreignKey->getColumn()}',\n";
    echo "            '{$foreignKey->getRelatedTable()}',\n";
    echo "            '{$foreignKey->getRelatedColumn()}',\n";
    echo "            'CASCADE'\n";
    echo "        );\n";
}
