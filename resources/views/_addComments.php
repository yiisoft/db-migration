<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\Service\Generate\PhpRenderer;

/**
 * Creates a call for the method `Yiisoft\Db\Migration\MigrationBuilder::addCommentOnTable()`.
 *
 * @var PhpRenderer $this
 * @var string $table The table name.
 * @var string $tableComment The table comment.
 */
?>

        $b->addCommentOnTable('<?= $table ?>', '<?= str_replace('\'', '\\\'', $tableComment) ?>');
