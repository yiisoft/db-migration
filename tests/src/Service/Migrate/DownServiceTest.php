<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Tests\Service\Migrate;

use Yiisoft\Yii\Db\Migration\Tests\Service\NamespaceMigrationServiceTest;

final class DownServiceTest extends NamespaceMigrationServiceTest
{
    public function testNotExistMigration(): void
    {
        $this->assertFalse($this->getDownService()->run('NotExistsMigration'));
    }
}
