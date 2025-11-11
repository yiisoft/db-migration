<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\Service\Generate\ForeignKey;
use Yiisoft\Db\Migration\Service\Generate\PhpRenderer;

/**
 * @var PhpRenderer $this
 * @var ForeignKey[] $foreignKeys Foreign keys.
 */

if (!empty($foreignKeys)) {
    echo " * Has foreign keys to the tables:\n";
    echo " *\n";
    foreach ($foreignKeys as $foreignKey) {
        echo " * - `{$foreignKey->getRelatedTable()}`\n";
    }
}
