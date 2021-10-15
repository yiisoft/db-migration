<?php

declare(strict_types=1);

/**
 * @var $this \Yiisoft\View\WebView
 * @var $foreignKeys \Yiisoft\Yii\Db\Migration\Service\Generate\ForeignKey[] the foreign keys
 */

if (!empty($foreignKeys)) {
    echo " * Has foreign keys to the tables:\n";
    echo " *\n";
    foreach ($foreignKeys as $foreignKey) {
        echo " * - `{$foreignKey->getRelatedTable()}`\n";
    }
}
