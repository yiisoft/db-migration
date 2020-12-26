<?php

declare(strict_types=1);

/**
 * @var $this \Yiisoft\View\WebView
 * @var $foreignKeys array the foreign keys
 */

if (!empty($foreignKeys)) {
    echo " * Has foreign keys to the tables:\n";
    echo " *\n";
    foreach ($foreignKeys as $fkData) {
        echo " * - `{$fkData['relatedTable']}`\n";
    }
}
