<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\Service\Generate\PhpRenderer;
use Yiisoft\Db\Migration\Command\CreateCommand;

/**
 * This view is used by {@see CreateCommand}.
 *
 * The following variables are available in this view:
 *
 * @var PhpRenderer $this
 * @var string $className The new migration class name without namespace.
 * @var string $namespace The new migration class namespace.
 * @var bool|null $transactional Whether the migration uses transactions. `null` keeps the template default.
 */

$transactional ??= false;

echo "<?php\n";

echo "\ndeclare(strict_types=1);\n";

if (!empty($namespace)) {
    echo "\nnamespace {$namespace};\n";
}
?>

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;
<?php if ($transactional): ?>
use Yiisoft\Db\Migration\TransactionalMigrationInterface;
<?php endif; ?>

final class <?= $className ?> implements RevertibleMigrationInterface<?=
    $transactional ? ", TransactionalMigrationInterface\n" : "\n"
?>
{
    public function up(MigrationBuilder $b): void
    {
        // TODO: Implement the logic to apply the migration.
    }

    public function down(MigrationBuilder $b): void
    {
        // TODO: Implement the logic to revert the migration.
    }
}
