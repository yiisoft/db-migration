<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Service\Generate;

use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Db\Connection\ConnectionInterface;

use function count;

/**
 * @internal
 */
final class ForeignKeyFactory
{
    public function __construct(
        private ConnectionInterface $db,
        private ?SymfonyStyle $io,
        private bool $useTablePrefix
    ) {
    }

    public function create(
        string $table,
        string $column,
        string $relatedTable,
        ?string $relatedColumn
    ): ForeignKey {
        /**
         * We're trying to get it from table schema.
         *
         * {@see https://github.com/yiisoft/yii2/issues/12748}
         */
        if ($relatedColumn === null) {
            $relatedColumn = 'id';
            $relatedTableSchema = $this->db->getTableSchema($relatedTable);
            if ($relatedTableSchema !== null) {
                $primaryKeyCount = count($relatedTableSchema->getPrimaryKey());
                if ($primaryKeyCount === 1) {
                    $relatedColumn = $relatedTableSchema->getPrimaryKey()[0];
                } elseif ($primaryKeyCount > 1) {
                    if ($this->io) {
                        $this->io->writeln(
                            "<fg=yellow> Related table for field \"{$column}\" exists, but primary key is composite. Default name \"id\" will be used for related field</>\n"
                        );
                    }
                } elseif ($primaryKeyCount === 0) {
                    if ($this->io) {
                        $this->io->writeln(
                            "<fg=yellow> Related table for field \"{$column}\" exists, but does not have a primary key. Default name \"id\" will be used for related field.</>\n"
                        );
                    }
                }
            }
        }

        return new ForeignKey(
            "idx-$table-$column",
            "fk-$table-$column",
            $column,
            $this->useTablePrefix ? '{{%' . $relatedTable . '}}' : $relatedTable,
            $relatedColumn,
        );
    }
}
