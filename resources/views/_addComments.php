<?php

declare(strict_types=1);

/**
 * Creates a call for the method `Yiisoft\Yii\Db\Migration\MigrationBuilder::addCommentOnTable()`
 *
 * @var $this \Yiisoft\View\WebView
 * @var $table string the name table
 * @var $tableComment string the comment table
 */
?>

        $b->addCommentOnTable('<?= $table ?>', '<?= str_replace('\'', '\\\'', $tableComment) ?>');
