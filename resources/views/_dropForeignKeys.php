<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\Service\Generate\ForeignKey;
use Yiisoft\Db\Migration\Service\Generate\PhpRenderer;

/**
 * @var PhpRenderer $this
 * @var string $table The table name.
 * @var ForeignKey[] $foreignKeys Foreign keys.
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
