<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Tests\Support\Helper;

final class ContainerConfig
{
    public bool $useTablePrefix = true;
    public ?bool $newMigrationTransactional = null;
}
