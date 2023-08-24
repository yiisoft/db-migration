<?php

declare(strict_types=1);

/**
 * Creates a call for the method `Yiisoft\Yii\Db\Migration\MigrationBuilder::addCommentOnTable()`.
 *
 * @var $this \Yiisoft\Yii\Db\Migration\Service\Generate\PhpRenderer
 * @var $table string The table name.
 * @var $tableComment string The table comment.
 */
?>

        $b->addCommentOnTable('<?= $table ?>', '<?= str_replace('\'', '\\\'', $tableComment) ?>');
